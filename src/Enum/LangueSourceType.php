<?php

declare(strict_types=1);

namespace App\Enum;

enum LangueSourceType: string
{
    use EnumTraits;

    case ADMIN = 'ADMIN';
    case ORIGINE = 'ORIGINE';
    case ORIGINE_SECONDAIRE = 'ORIGINE SECONDAIRE';
    case GROUPE = 'GROUPE';
    case ORIGINE_ET_GROUPE = 'ORIGINE et GROUPE';
    /** Langue transmise par les parents d'un héritier né en jeu (choisie par le scénariste). */
    case PARENTS = 'PARENTS';
    case LITTERATURE = 'LITTERATURE';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    /**
     * Rang d'une source quand une même langue est connue par plusieurs voies : plus le rang est petit, plus la source prime.
     * Origine > origine secondaire = parents > groupe > littérature > maître / bonus (toute autre source) > admin.
     */
    public static function priorityOf(string $source): int
    {
        return match ($source) {
            self::ORIGINE->value, self::ORIGINE_ET_GROUPE->value => 0,
            self::ORIGINE_SECONDAIRE->value, self::PARENTS->value => 1,
            self::GROUPE->value => 2,
            self::LITTERATURE->value => 3,
            self::ADMIN->value => 5,
            default => 4,
        };
    }

    /** @return array<string, string> */
    public static function getLabels(): array
    {
        return [
            self::ADMIN->value => 'Admin',
            self::ORIGINE->value => 'Origine',
            self::ORIGINE_SECONDAIRE->value => 'Origine secondaire',
            self::GROUPE->value => 'Groupe',
            self::ORIGINE_ET_GROUPE->value => 'Origine et groupe',
            self::PARENTS->value => 'Parents',
            self::LITTERATURE->value => 'Littérature',
        ];
    }
}
