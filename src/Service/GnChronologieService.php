<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Repository\GnRepository;
use DateTimeImmutable;
use DateTimeInterface;

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
    ) {
    }

    /**
     * Construit la chronologie à la date donnée.
     *
     * @return array{
     *     opus: list<array{gn: Gn, etat: string}>,
     *     precedent: ?Gn,
     *     suivant: ?Gn,
     *     jalon: ?array{libelle: string, date: DateTimeInterface, jours: int, niveau: string, gn: Gn}
     * }
     */
    public function construire(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        /** @var list<Gn> $gns */
        $gns = $this->gnRepository->findBy([], ['date_debut' => 'ASC']);
        $gns = array_values(array_filter($gns, static fn (Gn $gn): bool => null !== $gn->getDateDebut()));

        $precedent = null;
        $suivant = null;
        $opus = [];
        foreach ($gns as $gn) {
            $fin = $gn->getDateFin() ?? $gn->getDateDebut();
            if ($fin < $now) {
                $etat = 'done';
                $precedent = $gn;
            } elseif ($gn->getDateDebut() <= $now) {
                $etat = 'active';
            } else {
                $etat = null === $suivant ? 'next' : 'future';
                $suivant ??= $gn;
            }
            $opus[] = ['gn' => $gn, 'etat' => $etat];
        }

        return [
            'opus' => $opus,
            'precedent' => $precedent,
            'suivant' => $suivant,
            'jalon' => $this->prochainJalon($gns, $now),
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

    /**
     * Prochain jalon public à venir : verrouillage, puis début du jeu.
     *
     * @param list<Gn> $gns
     *
     * @return ?array{libelle: string, date: DateTimeInterface, jours: int, niveau: string, gn: Gn}
     */
    private function prochainJalon(array $gns, DateTimeImmutable $now): ?array
    {
        $jalons = [];
        foreach ($gns as $gn) {
            if (null !== $gn->getDateVerrouillage()) {
                $jalons[] = ['libelle' => 'Verrouillage des modifications', 'date' => $gn->getDateVerrouillage(), 'gn' => $gn];
            }
            $jalons[] = ['libelle' => 'Début du jeu', 'date' => $gn->getDateDebut(), 'gn' => $gn];
        }

        $jalons = array_filter($jalons, static fn (array $j): bool => $j['date'] >= $now);
        if ([] === $jalons) {
            return null;
        }

        usort($jalons, static fn (array $a, array $b): int => $a['date'] <=> $b['date']);
        $j = $jalons[0];
        $jours = (int) $now->setTime(0, 0)->diff(DateTimeImmutable::createFromInterface($j['date'])->setTime(0, 0))->format('%a');

        return $j + ['jours' => $jours, 'niveau' => self::niveau($jours)];
    }
}
