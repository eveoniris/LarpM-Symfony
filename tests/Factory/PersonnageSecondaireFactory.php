<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\PersonnageSecondaire;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Archétype de secours : un PersonnageSecondaire est un archétype prêt à jouer,
 * défini par une classe, et non un personnage réel.
 *
 * @extends PersistentObjectFactory<PersonnageSecondaire>
 */
final class PersonnageSecondaireFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return PersonnageSecondaire::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'classe' => ClasseFactory::new(),
        ];
    }
}
