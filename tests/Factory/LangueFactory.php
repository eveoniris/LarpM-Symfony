<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Langue;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Langue>
 */
final class LangueFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Langue::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->unique()->word(),
            'diffusion' => 0,
            'secret' => false,
            'groupeLangue' => GroupeLangueFactory::new(),
        ];
    }
}
