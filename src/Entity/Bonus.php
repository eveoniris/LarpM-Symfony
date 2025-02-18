<?php

namespace App\Entity;

use App\Enum\BonusType;
use App\Repository\BonusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusRepository::class)]
class Bonus extends BaseBonus
{
    // Pour faire voyager une éventuelle source
    private string $sourceTmp = '';

    public function getSourceTmp(): string
    {
        return $this->sourceTmp;
    }

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

    public function isLanguage(): bool
    {
        return BonusType::LANGUE->value === $this->getType();
    }

    public function setSourceTmp(string $sourceTmp): static
    {
        $this->sourceTmp = $sourceTmp;

        return $this;
    }
}
