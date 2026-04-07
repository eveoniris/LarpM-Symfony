<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\InterJeu;
use App\Enum\InterJeuEtat;
use DateTime;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<InterJeu>
 */
final class InterJeuFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return InterJeu::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'nom' => self::faker()->words(3, true),
            'anneeJeu' => self::faker()->numberBetween(900, 1100),
            'dateReel' => DateTime::createFromFormat('Y-m-d', '2025-01-01'),
            'etat' => InterJeuEtat::VALIDE,
        ];
    }
}
