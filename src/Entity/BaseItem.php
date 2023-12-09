<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'item')]
#[ORM\Index(columns: ['quality_id'], name: 'fk_item_qualite1_idx')]
#[ORM\Index(columns: ['statut_id'], name: 'fk_item_statut1_idx')]
#[ORM\Index(columns: ['objet_id'], name: 'fk_item_objet1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseItem', 'extended' => 'Item'])]
abstract class BaseItem
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $numero = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 2)]
    protected string $identification = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $special = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $couleur;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    protected \DateTime $date_creation;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    protected \DateTime $date_update;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $quantite = 0;

    #[ORM\ManyToOne(targetEntity: Qualite::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'qualite_id', referencedColumnName: 'id')]
    protected Quality $qualite;
    
    #[ORM\ManyToOne(targetEntity: Quality::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'quality_id', referencedColumnName: 'id')]
    protected Quality $quality;

    #[ORM\ManyToOne(targetEntity: Statut::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'statut_id', referencedColumnName: 'id', nullable: false)]
    protected Statut $statut;

    #[ORM\ManyToOne(targetEntity: Objet::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'objet_id', referencedColumnName: 'id', nullable: false)]
    protected Objet $objet;

    #[ORM\ManyToMany(targetEntity: Groupe::class, mappedBy: 'items')]
    protected Collection $groupes;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'items')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->groupes = new ArrayCollection();
        $this->personnages = new ArrayCollection();
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
     * Set the value of numero.
     */
    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     */
    public function getNumero(): int
    {
        return $this->numero;
    }

    /**
     * Set the value of identification.
     *
     * @param string $identification
     */
    public function setIdentification($identification): static
    {
        $this->identification = $identification;

        return $this;
    }

    /**
     * Get the value of identification.
     */
    public function getIdentification(): static
    {
        return $this->identification;
    }

    /**
     * Set the value of special.
     */
    public function setSpecial(string $special): Item
    {
        $this->special = $special;

        return $this;
    }

    /**
     * Get the value of special.
     */
    public function getSpecial(): string
    {
        return $this->special ?? '';
    }

    /**
     * Set the value of couleur.
     */
    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get the value of couleur.
     */
    public function getCouleur(): string
    {
        return $this->couleur ?? '';
    }

    /**
     * Set the value of date_creation.
     *
     * @param \DateTime $date_creation
     */
    public function setDateCreation($date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    /**
     * Get the value of date_creation.
     */
    public function getDateCreation(): static
    {
        return $this->date_creation;
    }

    /**
     * Set the value of date_update.
     *
     * @param \DateTime $date_update
     */
    public function setDateUpdate($date_update): static
    {
        $this->date_update = $date_update;

        return $this;
    }

    /**
     * Get the value of date_update.
     */
    public function getDateUpdate(): \DateTime
    {
        return $this->date_update;
    }

    /**
     * Set the value of quantite.
     */
    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Get the value of quantite.
     */
    public function getQuantite(): int
    {
        return $this->quantite;
    }

    /**
     * Set Quality entity (many to one).
     */
    public function setQuality(Quality $quality = null): static
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Get Quality entity (many to one).
     */
    public function getQuality(): Quality
    {
        return $this->quality;
    }

    /**
     * Set Statut entity (many to one).
     */
    public function setStatut(Statut $statut = null): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get Statut entity (many to one).
     */
    public function getStatut(): Statut
    {
        return $this->statut;
    }

    /**
     * Set Objet entity (many to one).
     */
    public function setObjet(Objet $objet = null): static
    {
        $this->objet = $objet;

        return $this;
    }

    /**
     * Get Objet entity (many to one).
     */
    public function getObjet(): Objet
    {
        return $this->objet;
    }

    /**
     * Add Groupe entity to collection.
     */
    public function addGroupe(Groupe $groupe): static
    {
        $this->groupes[] = $groupe;

        return $this;
    }

    /**
     * Remove Groupe entity from collection.
     */
    public function removeGroupe(Groupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity collection.
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
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

    public function __sleep()
    {
        return ['id', 'label', 'description', 'numero', 'identification', 'quality_id', 'special', 'couleur', 'date_creation', 'date_update', 'statut_id', 'objet_id', 'quantite'];
    }
}
