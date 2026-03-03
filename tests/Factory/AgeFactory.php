<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Age;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Age>
 */
final class AgeFactory extends PersistentProxyObjectFactory
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
