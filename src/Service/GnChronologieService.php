<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Enum\Role;
use App\Repository\GnRepository;
use App\Repository\GnTimelineStepRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Chronologie des GN : liste des jeux, intervalle courant (inter-jeu) et prochain jalon public.
 */
final readonly class GnChronologieService
{
    /** Seuil d'alerte jaune (jours avant l'échéance). */
    public const SEUIL_AVERTISSEMENT = 14;

    /** Seuil d'alerte rouge (jours avant l'échéance). */
    public const SEUIL_URGENT = 3;

    public function __construct(
        private GnRepository $gnRepository,
        private GnDateJeuCalculator $dateJeuCalculator,
        private GnJalonCalculator $jalonCalculator,
        private GnTimelineStepRepository $stepRepository,
        private Security $security,
    ) {
    }

    /**
     * Construit la chronologie à la date donnée.
     *
     * @return array{
     *     opus: list<array{gn: Gn, etat: string, etapes: list<GnTimelineStep>}>,
     *     indexMaintenant: int,
     *     precedent: ?Gn,
     *     suivant: ?Gn,
     *     jalon: ?array{libelle: string, date: DateTimeInterface, jours: int, niveau: string, gn: Gn},
     *     dateJeu: ?array{annee: float, estimation: bool, libelle: string}
     * }
     */
    public function construire(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        /** @var list<Gn> $gns */
        $gns = $this->gnRepository->findBy([], ['date_debut' => 'ASC']);
        $gns = array_values(array_filter($gns, static fn (Gn $gn): bool => null !== $gn->getDateDebut()));

        $toutes = $this->stepRepository->findAllOrdered();
        $visibles = [];
        foreach ($toutes as $etape) {
            if ($etape->getVisibilite()->estVisible(fn (Role $role): bool => $this->security->isGranted($role->value))) {
                $visibles[$etape->getGn()?->getId()][] = $etape;
            }
        }

        $precedent = null;
        $suivant = null;
        $actif = null;
        $opus = [];
        foreach ($gns as $gn) {
            $fin = $gn->getDateFin() ?? $gn->getDateDebut();
            if ($fin < $now) {
                $etat = 'done';
                $precedent = $gn;
            } elseif ($gn->getDateDebut() <= $now) {
                $etat = 'active';
                $actif = $gn;
            } else {
                $etat = null === $suivant ? 'next' : 'future';
                $suivant ??= $gn;
            }
            $opus[] = ['gn' => $gn, 'etat' => $etat, 'etapes' => $visibles[$gn->getId()] ?? []];
        }

        return [
            'opus' => $opus,
            'indexMaintenant' => \count(array_filter($opus, static fn (array $o): bool => 'done' === $o['etat'])),
            'precedent' => $precedent,
            'suivant' => $suivant,
            'jalon' => $this->jalonCalculator->prochain($gns, $toutes, $now),
            'dateJeu' => $this->dateJeuCalculator->calculer($gns, $precedent, $suivant, $actif, $now),
        ];
    }

    /**
     * Niveau d'alerte d'une échéance : normal, warn (14 j) ou err (3 j).
     */
    public static function niveau(int $jours): string
    {
        return match (true) {
            $jours <= self::SEUIL_URGENT => 'err',
            $jours <= self::SEUIL_AVERTISSEMENT => 'warn',
            default => 'info',
        };
    }
}
