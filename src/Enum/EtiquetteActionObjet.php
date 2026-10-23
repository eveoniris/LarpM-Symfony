<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce qu'il faut faire de l'objet rencontré avec son étiquette (aucune action quand il n'y a pas d'objet).
 */
enum EtiquetteActionObjet: string
{
    use EnumTraits;

    /** Objet de l'association trouvé dans l'enveloppe : à remettre au PC joueur avec son étiquette. */
    case REMETTRE_AU_PC = 'remettre_au_pc';
    /** Objet à étiquette orange trouvé dans l'enveloppe, ce qui n'est pas normal : "objet trouvé" au PC joueur. */
    case OBJET_TROUVE = 'objet_trouve';
    /** Objet déjà remis directement au PC joueur : le joueur le retirera au PC joueur. */
    case DEJA_AU_PC = 'deja_au_pc';

    public function label(): string
    {
        return match ($this) {
            self::REMETTRE_AU_PC => 'Objet à remettre au PC joueur',
            self::OBJET_TROUVE => 'Objet trouvé : à remettre au PC joueur',
            self::DEJA_AU_PC => 'Objet déjà au PC joueur (le joueur le retire au PC)',
        };
    }
}
