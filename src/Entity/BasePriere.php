<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'priere')]
#[ORM\Index(columns: ['sphere_id'], name: 'fk_priere_sphere1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePriere', 'extended' => 'Priere'])]
abstract class BasePriere
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected string $label;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $annonce = null;

    #[Column(name: 'documentUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[Column(type: Types::INTEGER)]
    protected int $niveau;

    #[ORM\ManyToOne(targetEntity: Sphere::class, inversedBy: 'prieres')]
    #[ORM\JoinColumn(name: 'sphere_id', referencedColumnName: 'id')]
    protected Sphere $sphere;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'prieres')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    /**
     * Add Personnage entity to collection.
     */
    public function addPersonnage(Personnage $personnage): static
    {
        $personnage->addPriere($this);
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Get the value of annonce.
     */
    public function getAnnonce(): string
    {
        return $this->annonce ?? '';
    }

    /**
     * Set the value of annonce.
     */
    public function setAnnonce(string $annonce): static
    {
        $this->annonce = $annonce;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of documentUrl.
     */
    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    /**
     * Set the value of documentUrl.
     */
    public function setDocumentUrl(string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of niveau.
     */
    public function getNiveau(): int
    {
        return $this->niveau;
    }

    /**
     * Set the value of niveau.
     *
     * @param int $niveau
     */
    public function setNiveau($niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * Get Personnage entity collection.
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    /**
     * Get Sphere entity (many to one).
     */
    public function getSphere(): ?Sphere
    {
        return $this->sphere;
    }

    /**
     * Set Sphere entity (many to one).
     */
    public function setSphere(?Sphere $sphere = null): static
    {
        $this->sphere = $sphere;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $personnage->removePriere($this);
        $this->personnages->removeElement($personnage);

        return $this;
    }
}
