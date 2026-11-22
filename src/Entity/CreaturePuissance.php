<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CreaturePuissanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Échelle de puissance relative des créatures, exprimée en équivalent XP (aide à la rédaction de BG).
 */
#[ORM\Entity(repositoryClass: CreaturePuissanceRepository::class)]
#[ORM\Table(name: 'creature_puissance')]
class CreaturePuissance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $famille = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $xp = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->nom ?? '';
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getFamille(): ?string
    {
        return $this->famille;
    }

    public function setFamille(?string $famille): static
    {
        $this->famille = $famille;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;

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
}
