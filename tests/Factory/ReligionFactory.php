<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Religion;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Religion>
 */
final class ReligionFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Religion::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label'  => self::faker()->unique()->words(2, true),
            'secret' => false,
        ];
    }
}
