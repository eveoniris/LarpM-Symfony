<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\GroupeLangue;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<GroupeLangue>
 */
final class GroupeLangueFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return GroupeLangue::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->unique()->word(),
            'couleur' => 'ffffff',
        ];
    }
}
