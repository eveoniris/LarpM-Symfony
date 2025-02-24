<?php

namespace App\Entity;

use App\Enum\BonusPeriode;
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
        // TODO if extend column like status

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

    public function getConditions(?array $row = null): array
    {
        return ($row ?? $this->getJsonData())['condition'] ?? [];
    }

    /**
     * 1 : un id simple : on aura le model directement
     * 2 : un tableau d'une dimension : le model à une condition
     * 3 : un tableau d'id : les models directement
     * 4 : un tableau de liste : les models avec possiblement des conditions.
     *
     * Ici ont converti tout en mode 4.
     */
    public function getData(?string $key = null): mixed
    {
        $data = $this->getJsonData() ?? [];

        return $data[$key] ?? $data[$key.'s'] ?? $data[rtrim($key, 's')] ?? $data;
    }

    public function getDataAsString(): string
    {
        return json_encode($this->getJsonData(), JSON_THROW_ON_ERROR) ?? '';
    }

    public function getDataAsList(?string $key = null, string $requiredParam = 'id'): array
    {
        $data = $this->getData($key);

        if (empty($data)) {
            // rien de valide
            return [];
        }

        // Mode 1
        if (is_numeric($data)) {
            return [[$requiredParam => $data]];
        }

        if (!is_array($data)) {
            // rien de valide
            return [];
        }

        // Mode 2
        if (isset($data[$requiredParam])) {
            return [$data];
        }

        foreach ($data as $row) {
            // Mode 3
            if (is_numeric($row)) {
                return [[$requiredParam => $row]];
            }
        }

        return [];
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

    public function isTypeAndPeriode(array|BonusType|null $types, array|BonusPeriode|null $periodes): bool
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        if (!is_array($periodes)) {
            $periodes = [$periodes];
        }

        foreach ($types as $type) {
            if (!$type instanceof BonusType) {
                $type = BonusType::tryFrom($type);
            }
            if ($this->getType() !== $type) {
                return false;
            }
        }

        foreach ($periodes as $periode) {
            if (!$periode instanceof BonusPeriode) {
                $periode = BonusPeriode::tryFrom($periode);
            }
            if ($this->getType() !== $periode) {
                return false;
            }
        }

        return true;
    }
}
