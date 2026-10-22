<?php

declare(strict_types=1);

namespace App\Enum;

enum Role: string
{
    use EnumTraits;

    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case CARTOGRAPHE = 'ROLE_CARTOGRAPHE';
    case COHERENCE = 'ROLE_COHERENCE';
    case DEV = 'ROLE_DEV';
    case GESTION = 'ROLE_GESTION';
    case MODERATEUR = 'ROLE_MODERATEUR';
    case ORGA = 'ROLE_ORGA';
    case REDACTEUR = 'ROLE_REDACTEUR';
    case REGLE = 'ROLE_REGLE';
    case WARGAME = 'ROLE_WARGAME';
    case TERRITOIRE = 'ROLE_TERRITOIRE';
    case SCENARISTE = 'ROLE_SCENARISTE';
    case STOCK = 'ROLE_STOCK';
    case USER = 'ROLE_USER';
    case ROLE_GROUPE_TRANSVERSE = 'ROLE_GROUPE_TRANSVERSE';
    case INTER_JEU = 'ROLE_INTER_JEU';
    case STATISTIQUE = 'ROLE_STATISTIQUE';
    case BETA_TEST = 'ROLE_BETA_TEST';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function getLabels(): array
    {
        return [
            self::SUPER_ADMIN->value => 'Droit divin, en plus de l\'admin, peu se faire passer pour autrui',
            self::ADMIN->value => 'Droit de modification sur tout',
            self::DEV->value => 'Droit de développeur',
            self::CARTOGRAPHE->value => 'Droit de modification sur le territoire et les fiefs',
            self::COHERENCE->value => 'Garant de la cohérence de l\'univers, droit de modification complet sur le menu Univers',
            self::MODERATEUR->value => 'Modération du forum',
            self::ORGA->value => 'Organisateur',
            self::REDACTEUR->value => 'Droit de modification des annonces',
            self::REGLE->value => 'Droit de modification sur les règles',
            self::SCENARISTE->value => 'Droit de modification sur le scénario, les groupes et le background',
            self::STOCK->value => 'Droit de modification sur le stock',
            self::USER->value => 'Utilisateur de larpManager',
            self::WARGAME->value => 'Jeu de domaine de larpManager',
            self::ROLE_GROUPE_TRANSVERSE->value => 'Gestion groupe transverse',
            self::TERRITOIRE->value => 'Gestion des territoires',
            self::INTER_JEU->value => 'Gestion des inter-jeux',
            self::GESTION->value => 'Gestion des données',
            self::STATISTIQUE->value => 'Accès au module Statistique',
            self::BETA_TEST->value => 'Bêta-testeur : accès aux développements en cours (attribué par le super admin uniquement)',
        ];
    }

    /**
     * Rôles que seul un super admin peut attribuer ou retirer.
     *
     * @return list<self>
     */
    public static function reservedToSuperAdmin(): array
    {
        return [self::SUPER_ADMIN, self::BETA_TEST];
    }

    /** @return list<string> */
    public static function reservedToSuperAdminValues(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::reservedToSuperAdmin());
    }
}
