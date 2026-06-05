<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Bonus;
use App\Enum\BonusType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Bonus>
 */
final class BonusFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Bonus::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'titre' => self::faker()->words(3, true),
            'type' => BonusType::RENOMME->value,
        ];
    }
}
