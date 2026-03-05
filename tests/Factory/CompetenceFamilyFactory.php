<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\CompetenceFamily;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<CompetenceFamily>
 */
final class CompetenceFamilyFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CompetenceFamily::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->unique()->words(2, true),
        ];
    }
}
