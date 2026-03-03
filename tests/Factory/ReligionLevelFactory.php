<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\ReligionLevel;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ReligionLevel>
 */
final class ReligionLevelFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ReligionLevel::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        static $indexSequence = 1;

        return [
            'label' => self::faker()->words(2, true),
            'index' => $indexSequence++,
        ];
    }
}
