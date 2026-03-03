<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Personnage;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Personnage>
 */
final class PersonnageFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Personnage::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'nom' => self::faker()->firstName(),
            'vivant' => true,
            'bracelet' => false,
            'xp' => 100,
            'groupe' => GroupeFactory::new(),
            'classe' => ClasseFactory::new(),
            'age' => AgeFactory::new(),
            'genre' => GenreFactory::new(),
            'territoire' => TerritoireFactory::new(),
            'user' => UserFactory::new(),
        ];
    }
}
