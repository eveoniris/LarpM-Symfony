<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use DateTimeImmutable;

/**
 * Date de jeu (année et mois) à un instant donné, selon l'écoulement du temps entre deux GN.
 */
final class GnDateJeuCalculator
{
    /** Écoulement par défaut : années de jeu par année réelle (5 ans de jeu tous les 2 ans). */
    public const RATIO_DEFAUT = 2.5;

    private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    /**
     * Date de jeu à l'instant donné, interpolée entre l'année de jeu du GN précédent et celle du suivant.
     * Sans GN suivant saisi, on estime jusqu'au 31/12 de l'année réelle suivante ("environ année XX").
     *
     * @param list<Gn> $gns triés par date de début
     *
     * @return ?array{annee: float, estimation: bool, libelle: string}
     */
    public function calculer(array $gns, ?Gn $precedent, ?Gn $suivant, ?Gn $actif, DateTimeImmutable $now): ?array
    {
        if (null !== $actif && $actif->getDateJeu() > 0) {
            return self::resultat((float) $actif->getDateJeu(), false);
        }

        if (null === $precedent || $precedent->getDateJeu() <= 0) {
            return null;
        }

        $base = (float) $precedent->getDateJeu();
        $debut = DateTimeImmutable::createFromInterface($precedent->getDateFin() ?? $precedent->getDateDebut());

        if (null !== $suivant && $suivant->getDateJeu() > 0) {
            $estimation = false;
            $cible = (float) $suivant->getDateJeu();
            $debut = DateTimeImmutable::createFromInterface($suivant->getDateTempsJeuDebut() ?? $debut);
            $fin = DateTimeImmutable::createFromInterface($suivant->getDateTempsJeuFin() ?? $suivant->getDateDebut());
        } else {
            $estimation = true;
            $fin = new DateTimeImmutable(((int) $now->format('Y') + 1) . '-12-31 23:59:59');
            $annees = max(0.0, ($fin->getTimestamp() - $debut->getTimestamp()) / 31_557_600);
            $cible = $base + ($this->ratio($gns) * $annees);
        }

        $duree = $fin->getTimestamp() - $debut->getTimestamp();
        $fraction = $duree <= 0 ? 1.0 : max(0.0, min(1.0, ($now->getTimestamp() - $debut->getTimestamp()) / $duree));

        return self::resultat($base + (($cible - $base) * $fraction), $estimation);
    }

    /**
     * Ratio années de jeu / années réelles observé entre les deux derniers GN datés.
     *
     * @param list<Gn> $gns
     */
    private function ratio(array $gns): float
    {
        $dates = array_values(array_filter($gns, static fn (Gn $gn): bool => $gn->getDateJeu() > 0));
        if (\count($dates) < 2) {
            return self::RATIO_DEFAUT;
        }

        [$a, $b] = array_slice($dates, -2);
        $annees = ($b->getDateDebut()->getTimestamp() - $a->getDateDebut()->getTimestamp()) / 31_557_600;

        return $annees > 0 ? ($b->getDateJeu() - $a->getDateJeu()) / $annees : self::RATIO_DEFAUT;
    }

    /**
     * @return array{annee: float, estimation: bool, libelle: string}
     */
    private static function resultat(float $annee, bool $estimation): array
    {
        $entiere = (int) floor($annee);
        $mois = min(11, (int) floor(($annee - $entiere) * 12));
        $libelle = $estimation ? \sprintf('environ année %d', (int) round($annee)) : \sprintf('%s %d', self::MOIS[$mois], $entiere);

        return ['annee' => $annee, 'estimation' => $estimation, 'libelle' => $libelle];
    }
}
