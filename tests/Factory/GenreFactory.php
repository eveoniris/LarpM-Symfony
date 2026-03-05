<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Genre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Genre>
 */
final class GenreFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Genre::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->unique()->word(),
            'description' => '',
        ];
    }
}
