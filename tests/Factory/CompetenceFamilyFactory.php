<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\CompetenceFamily;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CompetenceFamily>
 */
final class CompetenceFamilyFactory extends PersistentProxyObjectFactory
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
