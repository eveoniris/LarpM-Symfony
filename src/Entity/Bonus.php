<?php

namespace App\Entity;

use App\Enum\BonusType;
use App\Repository\BonusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusRepository::class)]
class Bonus extends BaseBonus
{
    public function isValid(): bool
    {
        // TODO only not expired and status = active => create the JOIN TABLE

        return true;
    }

    public function isXp(): bool
    {
        return BonusType::XP->value === $this->getType();
    }

    public function isCompetence(): bool
    {
        return BonusType::COMPETENCE->value === $this->getType();
    }
}
