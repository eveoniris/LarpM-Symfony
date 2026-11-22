<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;

/**
 * Calcul du bonus d'XP transmis à la mort d'un personnage (règles XP-05 et XP-06).
 *
 * Base = XP acquis en jeu par le défunt : on retire les gains de création, le bonus lié à l'âge,
 * la naissance de l'héritier et les bonus déjà reçus d'un autre défunt.
 * Bonus = tiers de la base arrondi au supérieur, ou moitié si le bénéficiaire est l'héritier.
 */
final class XpReportCalculateur
{
    public const string MODE_TIERS = 'tiers';
    public const string MODE_MOITIE = 'moitie';

    public const string LIBELLE_CREATION = 'Création de votre personnage';
    public const string LIBELLE_BONUS_AGE = "Bonus lié à l'age";
    public const string LIBELLE_NAISSANCE_HERITIER = "Naissance de l'héritier";
    public const string LIBELLE_SUPPRESSION_COMPETENCE = 'Suppression de la compétence';

    /** Préfixe du gain créé par le report (suivi du nom du défunt). */
    public const string PREFIXE_REPORT = "Bonus d'XP de la mort de ";

    /** XP acquis en jeu par le défunt, sans les gains qui ne comptent pas (plancher à 0). */
    public function base(Personnage $personnage): int
    {
        $base = 0;
        foreach ($personnage->getExperienceGains() as $gain) {
            $libelle = trim((string) $gain->getExplanation());
            if ($this->estExclu($libelle)) {
                continue;
            }
            $base += $gain->getXpGain();
        }

        return max(0, $base);
    }

    public function montant(int $base, string $mode): int
    {
        $base = max(0, $base);

        return match ($mode) {
            self::MODE_TIERS => intdiv(num1: $base + 2, num2: 3),
            self::MODE_MOITIE => intdiv(num1: $base + 1, num2: 2),
            default => throw new \InvalidArgumentException(\sprintf('Mode de report inconnu : %s', $mode)),
        };
    }

    /**
     * @return array{base: int, tiers: int, moitie: int}
     */
    public function montants(Personnage $defunt): array
    {
        $base = $this->base($defunt);

        return [
            'base' => $base,
            self::MODE_TIERS => $this->montant($base, self::MODE_TIERS),
            self::MODE_MOITIE => $this->montant($base, self::MODE_MOITIE),
        ];
    }

    private function estExclu(string $libelle): bool
    {
        return (
            \in_array($libelle, [self::LIBELLE_CREATION, self::LIBELLE_BONUS_AGE, self::LIBELLE_NAISSANCE_HERITIER], strict: true)
            || str_contains($libelle, self::LIBELLE_SUPPRESSION_COMPETENCE)
            || str_starts_with($libelle, self::PREFIXE_REPORT)
        );
    }
}
