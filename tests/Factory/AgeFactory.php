<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Age;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Age>
 */
final class AgeFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Age::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->words(2, true),
            'enableCreation' => true,
            'minimumValue' => 0,
        ];
    }
}
