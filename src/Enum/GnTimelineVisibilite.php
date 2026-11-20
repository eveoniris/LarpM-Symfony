<?php

declare(strict_types=1);

namespace App\Enum;

use Closure;

/**
 * Qui peut voir une étape de la chronologie d'un GN.
 */
enum GnTimelineVisibilite: string
{
    /** Tous les joueurs. */
    case PUBLIC = 'public';

    /** Équipe de jeu : scénaristes, orgas, règles, wargame (et admins). */
    case SECRET = 'secret';

    /** Technique : administrateurs uniquement. */
    case TECHNIQUE = 'technique';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public (tous les joueurs)',
            self::SECRET => 'Secret (scénaristes, orgas, règles, wargame)',
            self::TECHNIQUE => 'Technique (admins)',
        };
    }

    /**
     * Rôles dont l'un suffit pour voir l'étape (liste vide : visible de tous).
     *
     * @return list<Role>
     */
    public function rolesAutorises(): array
    {
        return match ($this) {
            self::PUBLIC => [],
            self::SECRET => [Role::ADMIN, Role::SCENARISTE, Role::ORGA, Role::REGLE, Role::WARGAME],
            self::TECHNIQUE => [Role::ADMIN],
        };
    }

    /**
     * @param Closure(Role): bool $aLeRole indique si l'utilisateur courant possède le rôle
     */
    public function estVisible(Closure $aLeRole): bool
    {
        $roles = $this->rolesAutorises();
        if ([] === $roles) {
            return true;
        }

        return array_any($roles, static fn (Role $role): bool => $aLeRole($role));
    }
}
