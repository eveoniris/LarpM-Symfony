<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TriggerType;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageTrigger extends BasePersonnageTrigger
{
    public function isDone(): bool
    {
        return (bool) $this->done;
    }

    /** @return array<string, mixed> */
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'TAG' => $this->getTag() instanceof TriggerType ? $this->getTag()->value : $this->getTag(),
        ];
    }
}
