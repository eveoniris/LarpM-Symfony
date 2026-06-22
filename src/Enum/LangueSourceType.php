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
    case LITTERATURE = 'LITTERATURE';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
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
            self::LITTERATURE->value => 'Littérature',
        ];
    }
}
