<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Personnage;
use App\Service\XpReportEligibilite;
use Twig\Attribute\AsTwigFunction;

/**
 * Données d'affichage du report d'XP à la mort sur la fiche d'un personnage.
 */
final readonly class XpReportExtension
{
    public function __construct(
        private XpReportEligibilite $eligibilite,
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
}
