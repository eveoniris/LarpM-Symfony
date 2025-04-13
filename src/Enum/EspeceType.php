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
            self::HUMANOID => $translator->trans('HUMANOID', domain: 'enum', locale: $locale),
            self::MAGIC => $translator->trans('MAGIC', domain: 'enum', locale: $locale),
            self::UNDEAD => $translator->trans('UNDEAD', domain: 'enum', locale: $locale),
            self::DIVINITY => $translator->trans('DIVINITY', domain: 'enum', locale: $locale),
            self::DEAMON => $translator->trans('DEAMON', domain: 'enum', locale: $locale),
            self::ETHEREAL => $translator->trans('ETHEREAL', domain: 'enum', locale: $locale),
        };
    }
}
