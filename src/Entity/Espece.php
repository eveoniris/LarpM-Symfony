<?php

namespace App\Entity;

use App\Enum\EspeceType;
use App\Repository\EspeceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EspeceRepository::class)]
class Espece
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column]
    private ?bool $secret = null;

    /**
     * @var Collection<int, Personnage>
     */
    #[ORM\ManyToMany(targetEntity: Personnage::class, inversedBy: 'especes')]
    private Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    public function getLabel(): string
    {
        return $this->getNom() ?? '';
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
        return $this->description ?? '';
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?EspeceType
    {
        return EspeceType::tryFrom($this->type);
    }

    public function setType(string|EspeceType|null $type): static
    {
        if ($type instanceof EspeceType) {
            $this->type = $type->value;

            return $this;
        }

        $this->type = $type;

        return $this;
    }

    public function isSecret(): ?bool
    {
        return $this->secret;
    }

    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }
}
