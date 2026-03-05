<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Gn;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Gn>
 */
final class GnFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Gn::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->words(3, true),
            'xp_creation' => 20,
            'actif' => false,
            'date_jeu' => 800, // in-game year (Hyborian Age setting)
        ];
    }
}
