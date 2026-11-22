<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use App\Entity\User;
use App\Entity\XpReport;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Qui peut transmettre un bonus d'XP, et qui peut le recevoir (règles XP-05 à XP-07).
 *
 * Mort disponible : personnage mort d'un joueur dont le bonus n'a pas encore été transmis et qui laisse au moins 1 XP.
 * Bénéficiaire possible : personnage vivant du joueur, qui n'a reçu aucun bonus et n'a pas encore participé à un GN
 * (une inscription à un GN futur ne compte pas).
 */
final readonly class XpReportEligibilite
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private XpReportCalculateur $calculateur,
        private PersonnageParticipation $participation,
    ) {
    }

    /**
     * @return list<array{mort: Personnage, tiers: int, moitie: int}>
     */
    public function mortsDisponibles(User $joueur): array
    {
        /** @var list<Personnage> $morts */
        $morts = $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Personnage::class, 'p')
            ->where('p.user = :joueur')
            ->andWhere('p.vivant = false')
            ->andWhere('NOT EXISTS (SELECT 1 FROM ' . XpReport::class . ' r WHERE r.personnageMort = p)')
            ->orderBy('p.id', 'ASC')
            ->setParameter('joueur', $joueur)
            ->getQuery()
            ->getResult();

        $disponibles = [];
        foreach ($morts as $mort) {
            $montants = $this->calculateur->montants($mort);
            if ($montants[XpReportCalculateur::MODE_TIERS] > 0) {
                $disponibles[] = [
                    'mort' => $mort,
                    XpReportCalculateur::MODE_TIERS => $montants[XpReportCalculateur::MODE_TIERS],
                    XpReportCalculateur::MODE_MOITIE => $montants[XpReportCalculateur::MODE_MOITIE],
                ];
            }
        }

        return $disponibles;
    }

    /** Vrai si ce personnage peut encore recevoir un bonus sans forcer. */
    public function peutRecevoir(Personnage $personnage): bool
    {
        if (!$personnage->getVivant() || null === $personnage->getId()) {
            return false;
        }

        $dejaServi = null !== $this->entityManager
            ->getRepository(XpReport::class)
            ->findOneBy(['personnageBeneficiaire' => $personnage]);

        return !$dejaServi && !$this->participation->aDejaParticipe($personnage);
    }

    /**
     * Personnages vivants du joueur qui peuvent recevoir un bonus.
     *
     * @return list<Personnage>
     */
    public function beneficiairesPossibles(User $joueur): array
    {
        /** @var list<Personnage> $vivants */
        $vivants = $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Personnage::class, 'p')
            ->where('p.user = :joueur')
            ->andWhere('p.vivant = true')
            ->orderBy('p.id', 'ASC')
            ->setParameter('joueur', $joueur)
            ->getQuery()
            ->getResult();

        return array_values(array_filter($vivants, $this->peutRecevoir(...)));
    }
}
