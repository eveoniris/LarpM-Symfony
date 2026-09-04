<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Les différents rôles qu'un personnage (ou un archétype) peut tenir au sein
 * d'une participation à un GN.
 *
 * L'ordre des cases est l'ordre de la chaîne de jeu : le joueur descend d'un cran
 * à chaque décès.
 */
enum PersonnageRoleType: string
{
    use EnumTraits;

    case PRINCIPAL = 'principal';
    case SUBSTITUTION = 'substitution';
    case RELEVE = 'releve';
    case ARCHETYPE = 'archetype';

    public function label(): string
    {
        return match ($this) {
            self::PRINCIPAL => 'Personnage principal',
            self::SUBSTITUTION => 'Personnage de substitution',
            self::RELEVE => 'Personnage de relève',
            self::ARCHETYPE => 'Archétype de secours',
        };
    }

    /**
     * Champ de l'entité Participant portant ce rôle.
     */
    public function field(): string
    {
        return match ($this) {
            self::PRINCIPAL => 'personnage',
            self::SUBSTITUTION => 'personnageSubstitution',
            self::RELEVE => 'personnageReleve',
            self::ARCHETYPE => 'personnageSecondaire',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PRINCIPAL => 'fa-user',
            self::SUBSTITUTION => 'fa-hourglass-half',
            self::RELEVE => 'fa-user-plus',
            self::ARCHETYPE => 'fa-masks-theater',
        };
    }

    /**
     * Lettre affichée dans les listes (badge compact).
     */
    public function badge(): string
    {
        return match ($this) {
            self::PRINCIPAL => 'P',
            self::SUBSTITUTION => 'S',
            self::RELEVE => 'R',
            self::ARCHETYPE => 'A',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PRINCIPAL => "Le personnage que vous jouez sur l'événement. C'est lui qui porte votre participation.",
            self::SUBSTITUTION => "Le personnage que vous jouez dans les instances situées hors du temps et du lieu de l'événement. Si vous n'en choisissez pas, votre personnage principal endosse les deux rôles.",
            self::RELEVE => 'Le personnage que vous reprenez si votre personnage principal (ou de substitution) vient à trépasser.',
            self::ARCHETYPE => "L'archétype prêt à jouer que vous endossez si votre personnage de relève vient lui aussi à trépasser.",
        };
    }
}
