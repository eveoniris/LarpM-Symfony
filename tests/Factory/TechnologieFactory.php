<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Technologie;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Technologie>
 */
final class TechnologieFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Technologie::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        $competenceFamily = CompetenceFamilyFactory::createOne();

        return [
            'label' => self::faker()->unique()->words(3, true),
            'description' => self::faker()->paragraph(),
            'documentUrl' => null,
            'secret' => false,
            'competenceFamily' => $competenceFamily,
        ];
    }
}
