<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum EspeceType: string implements TranslatableInterface
{
    use EnumTraits;

    case HUMANOID = 'HUMANOID';
    case MAGIC = 'MAGIC';
    case UNDEAD = 'UNDEAD';
    case DIVINITY = 'DIVINITY';
    case DEAMON = 'DEAMON';
    case ETHEREAL = 'ETHEREAL';

    /**
     * PHP usage
     * EspeceType::ETHEREAL->trans($this->translator).
     *
     * Twig usage
     * {{ espece.type | trans }}
     */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::HUMANOID => $translator->trans('enum.espece.humanoid', locale: $locale),
            self::MAGIC => $translator->trans('enum.espece.magic', locale: $locale),
            self::UNDEAD => $translator->trans('enum.espece.undead', locale: $locale),
            self::DIVINITY => $translator->trans('enum.espece.divinity', locale: $locale),
            self::DEAMON => $translator->trans('enum.espece.deamon', locale: $locale),
            self::ETHEREAL => $translator->trans('enum.espece.ethereal', locale: $locale),
        };
    }
}
