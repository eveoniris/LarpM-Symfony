<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExperienceGain;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\RenommeHistory;
use App\Entity\User;
use App\Enum\AjoutMasseType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique une même XP, renommée ou entrée de chronologie à une liste de personnages.
 * Écriture par lots (un flush tous les LOT personnages) et une ligne de log par ajout en masse.
 */
final readonly class AjoutMasseService
{
    private const int LOT = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<Personnage> $personnages
     *
     * @return int nombre de personnages modifiés
     */
    public function appliquer(AjoutMasseDemande $demande, array $personnages, ?User $admin): int
    {
        foreach ($personnages as $index => $personnage) {
            match ($demande->type) {
                AjoutMasseType::XP => $this->ajouterXp($personnage, $demande),
                AjoutMasseType::RENOMME => $this->ajouterRenomme($personnage, $demande),
                AjoutMasseType::CHRONOLOGIE => $this->ajouterChronologie($personnage, $demande),
            };
            if (0 === (($index + 1) % self::LOT)) {
                $this->entityManager->flush();
            }
        }

        $this->journaliser($demande, $personnages, $admin);
        $this->entityManager->flush();

        return \count($personnages);
    }

    private function ajouterXp(Personnage $personnage, AjoutMasseDemande $demande): void
    {
        $gain = new ExperienceGain();
        $gain->setPersonnage($personnage);
        $gain->setXpGain($demande->valeur());
        $gain->setExplanation($demande->motifPropre());
        $gain->setOperationDate($demande->date());
        $personnage->setXp($personnage->getXp() + $demande->valeur());

        $this->entityManager->persist($gain);
        $this->entityManager->persist($personnage);
    }

    private function ajouterRenomme(Personnage $personnage, AjoutMasseDemande $demande): void
    {
        $renomme = new RenommeHistory();
        $renomme->setPersonnage($personnage);
        $renomme->setRenomme($demande->valeur());
        $renomme->setExplication($demande->motifPropre());
        $renomme->setDate($demande->date());
        $personnage->addRenommeHistory($renomme);

        $this->entityManager->persist($renomme);
    }

    private function ajouterChronologie(Personnage $personnage, AjoutMasseDemande $demande): void
    {
        $chronologie = new PersonnageChronologie();
        $chronologie->setPersonnage($personnage);
        $chronologie->setAnnee($demande->valeur());
        $chronologie->setEvenement($demande->motifPropre());

        $this->entityManager->persist($chronologie);
    }

    /** @param list<Personnage> $personnages */
    private function journaliser(AjoutMasseDemande $demande, array $personnages, ?User $admin): void
    {
        $log = new LogAction();
        $log->setDate(new DateTime());
        $log->setUser($admin);
        $log->setType($demande->type->logType());
        $log->setData([
            'data' => [
                'masse' => true,
                'type' => $demande->type->value,
                'valeur' => $demande->valeur(),
                'motif' => $demande->motifPropre(),
                'personnage_ids' => array_map(static fn (Personnage $p): ?int => $p->getId(), $personnages),
            ],
        ]);
        $this->entityManager->persist($log);
    }
}
