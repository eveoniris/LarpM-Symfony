<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\GroupeGn;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<GroupeGn>
 */
final class GroupeGnFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return GroupeGn::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'groupe' => GroupeFactory::new(),
            'gn' => GnFactory::new(),
        ];
    }
}
