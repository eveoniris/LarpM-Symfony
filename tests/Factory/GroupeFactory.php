<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Groupe;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Groupe>
 */
final class GroupeFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Groupe::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        static $numSequence = 1;

        return [
            'nom' => self::faker()->words(2, true),
            'numero' => $numSequence++,
            'lock' => false,
            'pj' => true,
            'territoire' => TerritoireFactory::new(),
        ];
    }
}
