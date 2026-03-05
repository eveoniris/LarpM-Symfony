<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Level;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Level>
 */
final class LevelFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Level::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        static $indexSequence = 1;

        return [
            'index' => $indexSequence++,
            'label' => self::faker()->words(2, true),
            'cout' => self::faker()->numberBetween(1, 10),
            'cout_favori' => self::faker()->numberBetween(1, 8),
            'cout_meconu' => self::faker()->numberBetween(3, 15),
        ];
    }
}
