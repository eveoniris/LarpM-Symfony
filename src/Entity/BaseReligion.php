<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'religion')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseReligion', 'extended' => 'Religion'])]
abstract class BaseReligion
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $blason = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $description_orga = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $description_fervent;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $description_pratiquant;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $description_fanatique;

    #[Column(type: Types::BOOLEAN, nullable: true, options: ['default' => 0])]
    protected ?bool $secret = null;

    #[OneToMany(mappedBy: 'religion', targetEntity: PersonnagesReligions::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'religion_id', nullable: 'false')]
    protected Collection $personnagesReligions;

    #[OneToMany(mappedBy: 'religion', targetEntity: ReligionDescription::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'religion_id', nullable: 'false')]
    protected Collection $religionDescriptions;

    #[OneToMany(mappedBy: 'religion', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'religion_id', nullable: 'false')]
    protected Collection $territoires;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'religions')]
    protected Collection $personnages;

    #[ORM\ManyToMany(targetEntity: Sphere::class, mappedBy: 'religions')]
    protected Collection $spheres;

    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'religions')]
    protected Collection $territoireSecondaires;

    public function __construct()
    {
        $this->personnagesReligions = new ArrayCollection();
        $this->religionDescriptions = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->spheres = new ArrayCollection();
        $this->territoireSecondaires = new ArrayCollection();
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
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
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
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
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
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Set the value of blason.
     */
    public function setBlason(string $blason): static
    {
        $this->blason = $blason;

        return $this;
    }

    /**
     * Get the value of blason.
     */
    public function getBlason(): string
    {
        return $this->blason ?? '';
    }

    /**
     * Set the value of description_orga.
     */
    public function setDescriptionOrga(?string $description_orga): static
    {
        $this->description_orga = $description_orga;

        return $this;
    }

    /**
     * Get the value of description_orga.
     */
    public function getDescriptionOrga(): string
    {
        return $this->description_orga ?? '';
    }

    /**
     * Set the value of description_fervent.
     */
    public function setDescriptionFervent(?string $description_fervent): static
    {
        $this->description_fervent = $description_fervent;

        return $this;
    }

    /**
     * Get the value of description_fervent.
     */
    public function getDescriptionFervent(): string
    {
        return $this->description_fervent ?? '';
    }

    /**
     * Set the value of description_pratiquant.
     */
    public function setDescriptionPratiquant(?string $description_pratiquant): static
    {
        $this->description_pratiquant = $description_pratiquant;

        return $this;
    }

    /**
     * Get the value of description_pratiquant.
     */
    public function getDescriptionPratiquant(): string
    {
        return $this->description_pratiquant ?? '';
    }

    /**
     * Set the value of description_fanatique.
     */
    public function setDescriptionFanatique(?string $description_fanatique): static
    {
        $this->description_fanatique = $description_fanatique;

        return $this;
    }

    /**
     * Get the value of description_fanatique.
     */
    public function getDescriptionFanatique(): string
    {
        return $this->description_fanatique ?? '';
    }

    /**
     * Set the value of secret.
     */
    public function setSecret(int $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     */
    public function getSecret(): int
    {
        return $this->secret;
    }

    /**
     * Add PersonnagesReligions entity to collection (one to many).
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     */
    public function getPersonnagesReligions(): Collection
    {
        return $this->personnagesReligions;
    }

    /**
     * Add ReligionDescription entity to collection (one to many).
     */
    public function addReligionDescription(ReligionDescription $religionDescription): static
    {
        $this->religionDescriptions[] = $religionDescription;

        return $this;
    }

    /**
     * Remove ReligionDescription entity from collection (one to many).
     */
    public function removeReligionDescription(ReligionDescription $religionDescription): static
    {
        $this->religionDescriptions->removeElement($religionDescription);

        return $this;
    }

    /**
     * Get ReligionDescription entity collection (one to many).
     */
    public function getReligionDescriptions(): Collection
    {
        return $this->religionDescriptions;
    }

    /**
     * Add Territoire entity to collection (one to many).
     */
    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     */
    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     */
    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    /**
     * Add Personnage entity to collection.
     */
    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

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
     * Add Sphere entity to collection.
     */
    public function addSphere(Sphere $sphere): static
    {
        $this->spheres[] = $sphere;

        return $this;
    }

    /**
     * Remove Sphere entity from collection.
     */
    public function removeSphere(Sphere $sphere): static
    {
        $this->spheres->removeElement($sphere);

        return $this;
    }

    /**
     * Get Sphere entity collection.
     */
    public function getSpheres(): Collection
    {
        return $this->spheres;
    }

    /**
     * Fourni la liste des territoires ou la religion est une religion secondaire.
     */
    public function getTerritoireSecondaires(): Collection
    {
        return $this->territoireSecondaires;
    }

    /**
     * Ajoute un territoire dans la liste des territoires ou la religion est une religion secondaire.
     */
    public function addTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires[] = $territoire;

        return $this;
    }

    /**
     * Retire un territoire de la liste des territoires ou la religion est une religion secondaire.
     */
    public function removeTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires->removeElement($territoire);

        return $this;
    }
}
