<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Personnage;
use App\Entity\XpReport;
use App\Repository\XpReportRepository;
use App\Service\XpReportEligibilite;
use App\Service\XpReportHeritiers;
use Twig\Attribute\AsTwigFunction;

/**
 * Données d'affichage du report d'XP à la mort sur la fiche d'un personnage.
 */
final readonly class XpReportExtension
{
    public function __construct(
        private XpReportEligibilite $eligibilite,
        private XpReportHeritiers $heritiers,
        private XpReportRepository $repository,
    ) {
    }

    /**
     * Morts du même joueur dont le bonus peut être récupéré par ce personnage (liste vide si le personnage ne peut
     * plus rien recevoir).
     *
     * @return list<array{mort: Personnage, tiers: int, moitie: int}>
     */
    #[AsTwigFunction('xp_report_morts_pour')]
    public function mortsPour(Personnage $personnage): array
    {
        $joueur = $personnage->getUser();
        if (null === $joueur || !$this->eligibilite->peutRecevoir($personnage)) {
            return [];
        }

        return $this->eligibilite->mortsDisponibles($joueur);
    }

    /**
     * Héritiers du personnage (conception en jeu ou lien de lignée), avec le bonus qu'ils ont reçu.
     *
     * @return list<array{personnage: Personnage, gnEcoules: ?int, ageOk: bool, servi: ?XpReport}>
     */
    #[AsTwigFunction('xp_report_heritiers')]
    public function heritiersDe(Personnage $personnage): array
    {
        $lignes = [];
        foreach ($this->heritiers->heritiersDe($personnage) as $ligne) {
            $lignes[] = [
                'personnage' => $ligne['personnage'],
                'gnEcoules' => $ligne['gnEcoules'],
                'ageOk' => $ligne['ageOk'],
                'servi' => $this->repository->findOneByBeneficiaire($ligne['personnage']),
            ];
        }

        return $lignes;
    }

    /** Bonus d'XP reçu par ce personnage à la mort d'un autre, s'il y en a un. */
    #[AsTwigFunction('xp_report_recu')]
    public function recu(Personnage $personnage): ?XpReport
    {
        return $this->repository->findOneByBeneficiaire($personnage);
    }
}
