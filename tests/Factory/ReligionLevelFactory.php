<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\ReligionLevel;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ReligionLevel>
 */
final class ReligionLevelFactory extends PersistentObjectFactory
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
