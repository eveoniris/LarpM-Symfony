<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\GroupeGnDemande;
use App\Enum\GroupeGnDemandeType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<GroupeGnDemande>
 */
final class GroupeGnDemandeFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return GroupeGnDemande::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'type' => GroupeGnDemandeType::INVITATION,
            'participant' => ParticipantFactory::new(),
            'groupeGn' => GroupeGnFactory::new(),
        ];
    }
}
