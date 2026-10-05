<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\ExperienceGain;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ExperienceGain>
 */
final class ExperienceGainFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ExperienceGain::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'personnage' => PersonnageFactory::new(),
            'explanation' => self::faker()->sentence(),
            'xp_gain' => self::faker()->numberBetween(10, 100),
            'operation_date' => new \DateTime(),
        ];
    }
}
