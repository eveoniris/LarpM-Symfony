<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\OrigineBonusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['bonus_id'], name: 'fk_bonus_idx')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_territoire_idx')]
#[ORM\Entity(repositoryClass: OrigineBonusRepository::class)]
class OrigineBonus
{
    use BonusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territoire::class, cascade: ['persist', 'remove'], inversedBy: 'originesBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Territoire $territoire = null;

    #[ORM\ManyToOne(targetEntity: Bonus::class, cascade: ['persist', 'remove'], inversedBy: 'territoireBonus')]
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

    public function getTerritoire(): ?Territoire
    {
        return $this->territoire;
    }

    public function setTerritoire(?Territoire $territoire): static
    {
        $this->territoire = $territoire;

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
