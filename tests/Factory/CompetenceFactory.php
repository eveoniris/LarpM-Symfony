<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Competence;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Competence>
 */
final class CompetenceFactory extends PersistentProxyObjectFactory
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
