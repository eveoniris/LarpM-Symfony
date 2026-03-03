<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Genre;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Genre>
 */
final class GenreFactory extends PersistentProxyObjectFactory
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
