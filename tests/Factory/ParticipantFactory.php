<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Participant;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Participant>
 */
final class ParticipantFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Participant::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'gn' => GnFactory::new(),
        ];
    }
}
