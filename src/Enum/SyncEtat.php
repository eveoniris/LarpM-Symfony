<?php

declare(strict_types=1);

namespace App\Enum;

/** État d'un participant HelloAsso dans le tableau de rapprochement de la billetterie. */
enum SyncEtat: string
{
    use EnumTraits;

    case A_VALIDER = 'a_valider';
    case RAPPROCHE = 'rapproche';
    case IGNORE = 'ignore';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function getLabels(): array
    {
        return [
            self::A_VALIDER->value => 'À valider',
            self::RAPPROCHE->value => 'Rapproché',
            self::IGNORE->value => 'Ignoré',
        ];
    }
}
