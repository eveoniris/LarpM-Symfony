<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Religion;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Religion>
 */
final class ReligionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Religion::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->unique()->words(2, true),
        ];
    }
}
