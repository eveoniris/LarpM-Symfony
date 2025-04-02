<?php

namespace App\Entity;

use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Repository\BonusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonusRepository::class)]
class Bonus extends BaseBonus
{
    // Pour faire voyager une éventuelle source
    private string $sourceTmp = '';

    /**
     * @var Collection<int, Merveille>
     */
    #[ORM\OneToMany(mappedBy: 'bonus', targetEntity: Merveille::class)]
    private Collection $merveilles;

    public function __construct()
    {
        parent::__construct();
        $this->merveilles = new ArrayCollection();
    }

    public function getConditions(?array $row = null): array
    {
        return ($row ?? $this->getJsonData())['condition'] ?? [];
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

    /**
     * 1 : un id simple : on aura le model directement
     * 2 : un tableau d'une dimension : le model à une condition
     * 3 : un tableau d'id : les models directement
     * 4 : un tableau de liste : les models avec possiblement des conditions.
     *
     * Ici ont converti tout en mode 4.
     */
    public function getData(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->getJsonData() ?: [];

        if ($key) {
            return $data[$key] ?? $data[$key.'s'] ?? $data[rtrim($key, 's')] ?? $default;
        }

        return $data ?? $default;
    }

    public function getDataAsString(): string
    {
        return json_encode($this->getJsonData(), JSON_THROW_ON_ERROR) ?? '';
    }

    /** Alias Interface purpose */
    public function getLabel(): string
    {
        return $this->getTitre();
    }

    public function getSourceTmp(): string
    {
        return $this->sourceTmp;
    }

    public function setSourceTmp(string $sourceTmp): static
    {
        $this->sourceTmp = $sourceTmp;

        return $this;
    }

    public function isCompetence(): bool
    {
        return BonusType::COMPETENCE->value === $this->getType()->value;
    }

    public function isHeroisme(): bool
    {
        return BonusType::HEROISME->value === $this->getType()->value;
    }

    public function isIngredient(): bool
    {
        return BonusType::INGREDIENT->value === $this->getType()->value;
    }

    public function isItem(): bool
    {
        return BonusType::ITEM->value === $this->getType()->value;
    }

    public function isLanguage(): bool
    {
        return BonusType::LANGUE->value === $this->getType()->value;
    }

    public function isMateriel(): bool
    {
        return BonusType::MATERIEL->value === $this->getType()->value;
    }

    public function isMissionCommercial(): bool
    {
        return BonusType::MISSION_COMMERCIAL->value === $this->getType()->value;
    }

    public function isPugilat(): bool
    {
        return BonusType::PUGILAT->value === $this->getType()->value;
    }

    public function isRenomme(): bool
    {
        return BonusType::RENOMME->value === $this->getType()->value;
    }

    public function isRessource(): bool
    {
        return BonusType::RESSOURCE->value === $this->getType()->value;
    }

    public function isRichesse(): bool
    {
        return BonusType::RICHESSE->value === $this->getType()->value;
    }

    public function isTypeAndPeriode(array|BonusType|null $types, array|BonusPeriode|null $periodes = null): bool
    {
        if ($types) {
            if (!is_array($types)) {
                $types = [$types];
            }

            foreach ($types as $type) {
                if (!$type instanceof BonusType) {
                    $type = BonusType::tryFrom($type);
                }
                if (!$type instanceof BonusType) {
                    continue;
                }
                if ($this->getType()->value !== $type->value) {
                    return false;
                }
            }
        }

        if ($periodes) {
            if (!is_array($periodes)) {
                $periodes = [$periodes];
            }

            foreach ($periodes as $periode) {
                if (!$periode instanceof BonusPeriode) {
                    $periode = BonusPeriode::tryFrom($periode);
                }
                if (!$periode instanceof BonusPeriode) {
                    continue;
                }
                if ($this->getType()->value !== $periode->value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isValid(): bool
    {
        // TODO if extend column like status

        return true;
    }

    public function isXp(): bool
    {
        return BonusType::XP->value === $this->getType()->value;
    }

    /**
     * @return Collection<int, Merveille>
     */
    public function getMerveilles(): Collection
    {
        return $this->merveilles;
    }

    public function addMerveille(Merveille $merveille): static
    {
        if (!$this->merveilles->contains($merveille)) {
            $this->merveilles->add($merveille);
            $merveille->setBonus($this);
        }

        return $this;
    }

    public function removeMerveille(Merveille $merveille): static
    {
        if ($this->merveilles->removeElement($merveille)) {
            // set the owning side to null (unless already changed)
            if ($merveille->getBonus() === $this) {
                $merveille->setBonus(null);
            }
        }

        return $this;
    }
}
