<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Classe;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Classe>
 */
final class ClasseFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Classe::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'label_masculin' => self::faker()->words(2, true),
            'label_feminin' => self::faker()->words(2, true),
        ];
    }
}
