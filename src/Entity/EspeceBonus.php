<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EspeceBonusRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['bonus_id'], name: 'fk_espece_bonus_bonus_idx')]
#[ORM\Index(columns: ['espece_id'], name: 'fk_espece_bonus_espece_idx')]
#[ORM\Entity(repositoryClass: EspeceBonusRepository::class)]
class EspeceBonus
{
    use BonusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Espece::class, cascade: ['persist', 'remove'], inversedBy: 'especeBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Espece $espece = null;

    #[ORM\ManyToOne(targetEntity: Bonus::class, cascade: ['persist', 'remove'], inversedBy: 'especeBonus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bonus $bonus = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $creation_date = null;

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

    public function getCreationDate(): ?DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(DateTimeInterface $creation_date): static
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
