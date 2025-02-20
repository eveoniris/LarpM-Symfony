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

    /** Alias Interface purpose */
    public function getLabel(): string
    {
        return $this->getTitre();
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

    public function isRenomme(): bool
    {
        return BonusType::RENOMME->value === $this->getType();
    }

    public function isHeroisme(): bool
    {
    return BonusType::HEROISME->value === $this->getType();
    }

    public function isRessource(): bool
    {
        return BonusType::RESSOURCE->value === $this->getType();
    }

    public function isIngredient(): bool
    {
        return BonusType::INGREDIENT->value === $this->getType();
    }

    public function isRichesse(): bool
    {
        return BonusType::RICHESSE->value === $this->getType();
    }

    public function isMateriel(): bool
    {
        return BonusType::MATERIEL->value === $this->getType();
    }

    public function isItem(): bool
    {
        return BonusType::ITEM->value === $this->getType();
    }

    public function isMissionCommercial(): bool
    {
        return BonusType::MISSION_COMMERCIAL->value === $this->getType();
    }

    public function isPugilat(): bool
    {
        return BonusType::PUGILAT->value === $this->getType();
    }

    public function setSourceTmp(string $sourceTmp): static
    {
        $this->sourceTmp = $sourceTmp;

        return $this;
    }
}
