<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EspeceType;
use App\Repository\EspeceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SensitiveParameter;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description_secrete = null;

    #[ORM\Column(nullable: true)]
    private ?int $energieVitale = null;

    /**
     * @var Collection<int, EspeceBonus>
     */
    #[ORM\OneToMany(mappedBy: 'espece', targetEntity: EspeceBonus::class, cascade: ['persist', 'remove'])]
    private Collection $especeBonus;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
        $this->especeBonus = new ArrayCollection();
    }

    public function addPersonnage(Personnage $personnage): static
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
        }

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->getNom() ?? '';
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

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function getType(): ?EspeceType
    {
        return null !== $this->type ? EspeceType::tryFrom($this->type) : null;
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

    public function isOmbrelin(): bool
    {
        return 'OMBRELIN' === strtoupper($this->nom ?? '');
    }

    public function isProfond(): bool
    {
        return 'PROFOND' === strtoupper($this->nom ?? '');
    }

    public function isSecret(): ?bool
    {
        return $this->secret;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function setSecret(#[SensitiveParameter] bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function getDescriptionSecrete(): ?string
    {
        return $this->description_secrete;
    }

    public function setDescriptionSecrete(?string $description_secrete): static
    {
        $this->description_secrete = $description_secrete;

        return $this;
    }

    public function getEnergieVitale(): ?int
    {
        return $this->energieVitale;
    }

    public function setEnergieVitale(?int $energieVitale): static
    {
        $this->energieVitale = $energieVitale;

        return $this;
    }

    /**
     * @return Collection<int, EspeceBonus>
     */
    public function getEspeceBonus(): Collection
    {
        return $this->especeBonus;
    }

    public function addEspeceBonus(EspeceBonus $especeBonus): static
    {
        if (!$this->especeBonus->contains($especeBonus)) {
            $this->especeBonus->add($especeBonus);
            $especeBonus->setEspece($this);
        }

        return $this;
    }

    public function removeEspeceBonus(EspeceBonus $especeBonus): static
    {
        $this->especeBonus->removeElement($especeBonus);

        return $this;
    }
}
