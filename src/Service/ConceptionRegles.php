<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\ConceptionIssue;
use InvalidArgumentException;

/**
 * Règles de la conception d'un héritier (pures, sans base de données).
 *
 * - On ne peut plus concevoir à partir de 45 ans, début de la tranche d'âge « Mûr ».
 * - Le tirage est un d20 (1 à 20, pas de 0). 1 : conséquence pour les parents ; 2 à 4 : échec ; 5 à 10 : une fille ; 10 à 15 :
 *   un garçon (10 convient aux deux, le scénariste choisit) ; 16 à 19 : réussite, genre au choix ; 20 : jumeaux.
 *   L'issue est une SUGGESTION : le scénariste confirme la création de la fiche, ou gère la création lui-même plus tard.
 */
final class ConceptionRegles
{
    /** Âge réel à partir duquel un personnage ne peut plus concevoir (tranche « Mûr »). */
    public const int AGE_LIMITE = 45;

    /** Libellé de la tranche d'âge des héritiers nés en jeu (0 an). */
    public const string AGE_HERITIER = 'Enfant héritier';

    public const int TIRAGE_MIN = 1;

    public const int TIRAGE_MAX = 20;

    /**
     * Tire un d20. La source de nombres est injectable pour les tests.
     *
     * @param (callable(int, int): int)|null $source reçoit les bornes min et max, retourne un entier entre les deux
     */
    public function tirer(?callable $source = null): int
    {
        $tirage = ($source ?? random_int(...))(self::TIRAGE_MIN, self::TIRAGE_MAX);

        return $this->verifierTirage($tirage);
    }

    /**
     * @throws InvalidArgumentException si le tirage n'est pas compris entre 1 et 20
     */
    public function issue(int $tirage): ConceptionIssue
    {
        $this->verifierTirage($tirage);

        return match (true) {
            1 === $tirage => ConceptionIssue::CONSEQUENCE_PARENTS,
            $tirage <= 4 => ConceptionIssue::ECHEC,
            $tirage <= 9 => ConceptionIssue::FILLE,
            10 === $tirage => ConceptionIssue::FILLE_OU_GARCON,
            $tirage <= 15 => ConceptionIssue::GARCON,
            $tirage <= 19 => ConceptionIssue::GENRE_AU_CHOIX,
            default => ConceptionIssue::JUMEAUX,
        };
    }

    /**
     * Un âge inconnu n'empêche pas la conception : le scénariste vérifie.
     */
    public function ageAutorise(?int $ageReel): bool
    {
        return null === $ageReel || $ageReel < self::AGE_LIMITE;
    }

    /**
     * Message à afficher quand un parent ne peut pas concevoir (ou null s'il le peut).
     */
    public function anomalieAge(string $parent, ?int $ageReel): ?string
    {
        if ($this->ageAutorise($ageReel)) {
            return null;
        }

        return \sprintf('%s a %d ans : on ne peut plus concevoir à partir de %d ans (âge mûr).', $parent, $ageReel, self::AGE_LIMITE);
    }

    private function verifierTirage(int $tirage): int
    {
        if ($tirage < self::TIRAGE_MIN || $tirage > self::TIRAGE_MAX) {
            throw new InvalidArgumentException(\sprintf('Le tirage est compris entre %d et %d.', self::TIRAGE_MIN, self::TIRAGE_MAX));
        }

        return $tirage;
    }
}
