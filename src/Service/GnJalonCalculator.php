<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Enum\GnTimelineVisibilite;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Prochain jalon public à venir (verrouillage, début de jeu, étapes publiques) avec son niveau d'alerte.
 */
final class GnJalonCalculator
{
    /**
     * Prochain jalon public à venir : verrouillage, puis début du jeu.
     *
     * @param list<Gn> $gns
     * @param list<GnTimelineStep> $etapes toutes les étapes (seules les publiques font des jalons)
     *
     * @return ?array{libelle: string, date: DateTimeInterface, jours: int, niveau: string, gn: Gn}
     */
    public function prochain(array $gns, array $etapes, DateTimeImmutable $now): ?array
    {
        $jalons = [];
        foreach ($gns as $gn) {
            if (null !== $gn->getDateVerrouillage()) {
                $jalons[] = ['libelle' => 'Verrouillage des modifications', 'date' => $gn->getDateVerrouillage(), 'gn' => $gn];
            }
            $jalons[] = ['libelle' => 'Début du jeu', 'date' => $gn->getDateDebut(), 'gn' => $gn];
        }

        foreach ($etapes as $etape) {
            if (GnTimelineVisibilite::PUBLIC === $etape->getVisibilite() && null !== $etape->getGn() && null !== $etape->getDatePrevue() && GnTimelineType::VERROUILLAGE !== $etape->getType()) {
                $jalons[] = ['libelle' => $etape->getLibelle(), 'date' => $etape->getDatePrevue(), 'gn' => $etape->getGn()];
            }
        }

        $jalons = array_filter($jalons, static fn (array $j): bool => $j['date'] >= $now);
        if ([] === $jalons) {
            return null;
        }

        usort($jalons, static fn (array $a, array $b): int => $a['date'] <=> $b['date']);
        $j = $jalons[0];
        $jours = (int) $now->setTime(0, 0)->diff(DateTimeImmutable::createFromInterface($j['date'])->setTime(0, 0))->format('%a');

        return $j + ['jours' => $jours, 'niveau' => GnChronologieService::niveau($jours)];
    }
}
