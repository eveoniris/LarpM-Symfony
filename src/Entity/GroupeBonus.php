<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\GroupeBonusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['bonus_id'], name: 'fk_bonus_idx')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_idx')]
#[ORM\Entity(repositoryClass: GroupeBonusRepository::class)]
class GroupeBonus
{
    use BonusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Groupe::class, cascade: ['persist', 'remove'], inversedBy: 'groupeBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;

    #[ORM\ManyToOne(targetEntity: Bonus::class, cascade: ['persist', 'remove'], inversedBy: 'groupeBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bonus $bonus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creation_date = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $status = null;

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

    public function setCreationDate(\DateTimeInterface $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }
}
