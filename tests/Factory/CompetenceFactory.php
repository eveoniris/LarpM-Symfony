<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Competence;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Competence>
 */
final class CompetenceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Competence::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'competenceFamily' => CompetenceFamilyFactory::new(),
            'level' => LevelFactory::new(),
        ];
    }
}
