<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Avancement d'une conception d'héritier, de son enregistrement à la décision du scénariste.
 */
enum ConceptionStatut: string
{
    use EnumTraits;

    /** Enregistrée depuis un retour de jeu, en attente de la décision du scénariste. */
    case EN_ATTENTE = 'en_attente';
    /** Le scénariste a confirmé : la ou les fiches d'héritier ont été créées. */
    case HERITIERS_CREES = 'heritiers_crees';
    /** Le scénariste gérera la création lui-même plus tard dans le jeu (choix consigné). */
    case A_GERER_PLUS_TARD = 'a_gerer_plus_tard';
    /** Échec ou conséquence pour les parents : pas d'héritier, décision consignée. */
    case CLOTUREE = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente du scénariste',
            self::HERITIERS_CREES => 'Héritier(s) créé(s)',
            self::A_GERER_PLUS_TARD => 'À gérer plus tard par le scénariste',
            self::CLOTUREE => 'Clôturée',
        };
    }

    /** Le scénariste peut encore décider. */
    public function estEnAttente(): bool
    {
        return self::EN_ATTENTE === $this;
    }
}
