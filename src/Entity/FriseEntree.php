<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FriseEntreeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Peuple, civilisation ou événement placé sous un âge de la chronologie du monde.
 */
#[ORM\Entity(repositoryClass: FriseEntreeRepository::class)]
#[ORM\Table(name: 'frise_entree')]
class FriseEntree
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FriseAge::class, inversedBy: 'entrees')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?FriseAge $age = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $ordre = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->titre ?? '';
    }

    public function getAge(): ?FriseAge
    {
        return $this->age;
    }

    public function setAge(?FriseAge $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
