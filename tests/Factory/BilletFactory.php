<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Billet;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Billet>
 */
final class BilletFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Billet::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => 'Billet test',
            'gn' => GnFactory::new(),
            'user' => UserFactory::new(),
        ];
    }
}
