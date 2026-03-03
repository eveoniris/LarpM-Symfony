<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Territoire;
use App\Enum\TerritoireStatut;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Territoire>
 */
final class TerritoireFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Territoire::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        $faker = self::faker();

        return [
            'nom' => $faker->words(2, true),
            'description' => $faker->unique()->sentence(),
            'capitale' => $faker->unique()->city(),
            'politique' => $faker->unique()->word(),
            'dirigeant' => $faker->unique()->name(),
            'statut' => TerritoireStatut::STABLE,
            'tresor' => 100,
            'resistance' => 10,
            'ordre_social' => 0,
        ];
    }
}
