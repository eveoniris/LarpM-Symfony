<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\PersonnageBonusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['bonus_id'], name: 'fk_bonus_idx')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_idx')]
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
    private ?\DateTimeInterface $creation_date = null;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $status = null;

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

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(?\DateTimeInterface $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }
}
