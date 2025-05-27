<?php

namespace App\Enum;

enum Role: string
{
    use EnumTraits;

    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case CARTOGRAPHE = 'ROLE_CARTOGRAPHE';
    case DEV = 'ROLE_DEV';
    case GESTION = 'ROLE_GESTION';
    case MODERATEUR = 'ROLE_MODERATEUR';
    case ORGA = 'ROLE_ORGA';
    case REDACTEUR = 'ROLE_REDACTEUR';
    case REGLE = 'ROLE_REGLE';
    case WARGAME = 'ROLE_WARGAME';
    case SCENARISTE = 'ROLE_SCENARISTE';
    case STOCK = 'ROLE_STOCK';
    case USER = 'ROLE_USER';
    case ROLE_GROUPE_TRANSVERSE = 'ROLE_GROUPE_TRANSVERSE';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    public static function getLabels(): array
    {
        return [
            //self::SUPER_ADMIN->value => 'Droit divin',
            self::ADMIN->value => 'Droit de modification sur tout',
            self::DEV->value => 'Droit de développeur',
            self::CARTOGRAPHE->value => 'Droit de modification sur l\'univers',
            self::MODERATEUR->value => 'Modération du forum',
            self::ORGA->value => 'Organisateur',
            self::REDACTEUR->value => 'Droit de modification des annonces',
            self::REGLE->value => 'Droit de modification sur les règles',
            self::SCENARISTE->value => 'Droit de modification sur le scénario, les groupes et le background',
            self::STOCK->value => 'Droit de modification sur le stock',
            self::USER->value => 'Utilisateur de larpManager',
            self::WARGAME->value => 'Jeu de domaine de larpManager',
            self::ROLE_GROUPE_TRANSVERSE->value => 'Gestion groupe transverse',
        ];
    }

}
