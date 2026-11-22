<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FriseAgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Âge de la chronologie du monde (page réservée aux scénaristes).
 */
#[ORM\Entity(repositoryClass: FriseAgeRepository::class)]
#[ORM\Table(name: 'frise_age')]
class FriseAge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $periode = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $ordre = 0;

    /** @var Collection<int, FriseEntree> */
    #[ORM\OneToMany(targetEntity: FriseEntree::class, mappedBy: 'age', cascade: ['remove'])]
    #[ORM\OrderBy(['ordre' => 'ASC', 'id' => 'ASC'])]
    private Collection $entrees;

    public function __construct()
    {
        $this->entrees = new ArrayCollection();
    }

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

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(?string $periode): static
    {
        $this->periode = $periode;

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

    /** @return Collection<int, FriseEntree> */
    public function getEntrees(): Collection
    {
        return $this->entrees;
    }
}
