<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PersonnageBonusSource;
use App\Repository\PersonnageBonusRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['bonus_id'], name: 'fk_bonus_idx')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_idx')]
#[ORM\Index(columns: ['espece_id'], name: 'fk_personnage_bonus_espece_idx')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_personnage_bonus_territoire_idx')]
#[ORM\UniqueConstraint(name: 'uniq_personnage_bonus_origine', columns: ['personnage_id', 'bonus_id', 'territoire_id'])]
#[ORM\Entity(repositoryClass: PersonnageBonusRepository::class)]
class PersonnageBonus
{
    use BonusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, cascade: ['persist', 'remove'], inversedBy: 'personnageBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnage $personnage = null;

    #[ORM\ManyToOne(targetEntity: Bonus::class, cascade: ['persist', 'remove'], inversedBy: 'personnageBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bonus $bonus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $creation_date = null;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Espece::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Espece $espece = null;

    /** Origine ayant donné le bonus (source ORIGINE), figée à l'acquisition. */
    #[ORM\ManyToOne(targetEntity: Territoire::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territoire $territoire = null;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $source = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getBonus(): ?Bonus
    {
        return $this->bonus;
    }

    public function setBonus(?Bonus $bonus): static
    {
        $this->bonus = $bonus;

        return $this;
    }

    public function getCreationDate(): ?DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(?DateTimeInterface $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getEspece(): ?Espece
    {
        return $this->espece;
    }

    public function setEspece(?Espece $espece): static
    {
        $this->espece = $espece;

        return $this;
    }

    public function getTerritoire(): ?Territoire
    {
        return $this->territoire;
    }

    public function setTerritoire(?Territoire $territoire): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    public function getSource(): ?PersonnageBonusSource
    {
        return $this->source ? PersonnageBonusSource::tryFrom($this->source) : null;
    }

    public function setSource(string|PersonnageBonusSource|null $source): static
    {
        $this->source = $source instanceof PersonnageBonusSource ? $source->value : $source;

        return $this;
    }
}
