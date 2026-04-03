<?php

declare(strict_types=1);

namespace App\Enum;

enum InterJeuEtat: string
{
    use EnumTraits;

    case A_VALIDER = 'a_valider';
    case VALIDE = 'valide';
    case TERMINE = 'termine';
    case REFUSE = 'refuse';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function getLabels(): array
    {
        return [
            self::A_VALIDER->value => 'À valider',
            self::VALIDE->value => 'Validé',
            self::TERMINE->value => 'Terminé',
            self::REFUSE->value => 'Refusé',
        ];
    }
}
