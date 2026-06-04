<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\FicheRetourGroupe;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<FicheRetourGroupe> */
final class FicheRetourGroupeFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FicheRetourGroupe::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'groupeGn' => GroupeGnFactory::new(),
            'pieces_argent' => self::faker()->numberBetween(0, 100),
            'pieces_or' => self::faker()->numberBetween(0, 50),
            'nb_ingredients' => self::faker()->numberBetween(0, 20),
            'nb_potions' => self::faker()->numberBetween(0, 10),
            'armement' => self::faker()->numberBetween(0, 30),
            'chevaux' => self::faker()->numberBetween(0, 10),
            'fruits_legumes' => self::faker()->numberBetween(0, 20),
            'matieres_simples' => self::faker()->numberBetween(0, 20),
            'sel' => self::faker()->numberBetween(0, 10),
            'betail' => self::faker()->numberBetween(0, 10),
            'coton' => self::faker()->numberBetween(0, 10),
            'gemmes' => self::faker()->numberBetween(0, 10),
            'moutons' => self::faker()->numberBetween(0, 10),
            'soie' => self::faker()->numberBetween(0, 10),
            'bois' => self::faker()->numberBetween(0, 10),
            'esclaves' => self::faker()->numberBetween(0, 10),
            'ivoire' => self::faker()->numberBetween(0, 10),
            'pierre' => self::faker()->numberBetween(0, 10),
            'teinture' => self::faker()->numberBetween(0, 10),
            'cereales' => self::faker()->numberBetween(0, 10),
            'fourrures' => self::faker()->numberBetween(0, 10),
            'matieres_precieux' => self::faker()->numberBetween(0, 10),
            'poisson' => self::faker()->numberBetween(0, 10),
            'vin' => self::faker()->numberBetween(0, 10),
            'commentaire' => null,
            'createdBy' => null,
            'updatedBy' => null,
        ];
    }
}
