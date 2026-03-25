<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Espece;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Espece>
 */
final class EspeceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Espece::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'nom' => 'Humain',
            'secret' => false,
        ];
    }
}
