<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Issue SUGGÉRÉE par le tirage d'une conception d'héritier. Le scénariste confirme (ou gère la création lui-même plus tard).
 */
enum ConceptionIssue: string
{
    use EnumTraits;

    /** Tirage de 1 : pas d'héritier, et une conséquence pour les parents dont le scénariste décide. */
    case CONSEQUENCE_PARENTS = 'consequence_parents';
    /** Tirage de 2 à 4. */
    case ECHEC = 'echec';
    /** Tirage de 11 à 15. */
    case GARCON = 'garcon';
    /** Tirage de 10 : garçon comme fille sont possibles, le scénariste choisit. */
    case FILLE_OU_GARCON = 'fille_ou_garcon';
    /** Tirage de 5 à 9. */
    case FILLE = 'fille';
    /** Tirage de 16 à 19 : réussite, le genre est au choix. */
    case GENRE_AU_CHOIX = 'genre_au_choix';
    /** Tirage de 20 : une fille et un garçon. */
    case JUMEAUX = 'jumeaux';

    public function label(): string
    {
        return match ($this) {
            self::CONSEQUENCE_PARENTS => 'Pas d\'héritier, conséquence pour les parents (au scénariste)',
            self::ECHEC => 'Échec',
            self::GARCON => 'Réussite : un garçon',
            self::FILLE_OU_GARCON => 'Réussite : une fille ou un garçon (au choix)',
            self::FILLE => 'Réussite : une fille',
            self::GENRE_AU_CHOIX => 'Réussite : genre au choix',
            self::JUMEAUX => 'Réussite : jumeaux, une fille et un garçon',
        };
    }

    /** Un ou plusieurs héritiers naissent. */
    public function estReussite(): bool
    {
        return !\in_array($this, [self::CONSEQUENCE_PARENTS, self::ECHEC], strict: true);
    }

    /** Nombre de fiches d'héritier à créer si le scénariste confirme. */
    public function nombreHeritiers(): int
    {
        return match (true) {
            self::JUMEAUX === $this => 2,
            $this->estReussite() => 1,
            default => 0,
        };
    }
}
