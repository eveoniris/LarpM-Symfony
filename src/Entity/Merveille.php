<?php

namespace App\Entity;

use App\Repository\MerveilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MerveilleRepository::class)]
class Merveille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description_scenariste = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description_cartographe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_creatation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_destruction = null;

    #[ORM\ManyToOne(inversedBy: 'merveilles')]
    private ?Territoire $territoire = null;

    #[ORM\ManyToOne(inversedBy: 'merveilles')]
    private ?Bonus $bonus = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescriptionScenariste(): ?string
    {
        return $this->description_scenariste;
    }

    public function setDescriptionScenariste(?string $description_scenariste): static
    {
        $this->description_scenariste = $description_scenariste;

        return $this;
    }

    public function getDescriptionCartographe(): ?string
    {
        return $this->description_cartographe;
    }

    public function setDescriptionCartographe(?string $description_cartographe): static
    {
        $this->description_cartographe = $description_cartographe;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreatation(): ?\DateTimeInterface
    {
        return $this->date_creatation;
    }

    public function setDateCreatation(?\DateTimeInterface $date_creatation): static
    {
        $this->date_creatation = $date_creatation;

        return $this;
    }

    public function getDateDestruction(): ?\DateTimeInterface
    {
        return $this->date_destruction;
    }

    public function setDateDestruction(?\DateTimeInterface $date_destruction): static
    {
        $this->date_destruction = $date_destruction;

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
}
