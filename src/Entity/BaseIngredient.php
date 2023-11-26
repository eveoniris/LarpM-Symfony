<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'ingredient')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIngredient', 'extended' => 'Ingredient'])]
abstract class BaseIngredient
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $niveau = 0;

    /**
     * @Column(type="string", length=45)
     */
    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $dose = '';

    #[OneToMany(mappedBy: 'ingredient', targetEntity: GroupeHasIngredient::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'ingredient_id', nullable: 'false')]
    protected ?ArrayCollection $groupeHasIngredients;

    #[OneToMany(mappedBy: 'ingredient', targetEntity: PersonnageIngredient::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'ingredient_id', nullable: 'false')]
    protected ?ArrayCollection $personnageIngredients;

    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'ingredients')]
    protected Collection $territoires;

    public function __construct()
    {
        $this->groupeHasIngredients = new ArrayCollection();
        $this->personnageIngredients = new ArrayCollection();
        $this->territoires = new ArrayCollection();
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): string
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
        return $this->description;
    }

    /**
     * Set the value of niveau.
     */
    public function setNiveau(int $niveau): static
    {
        $this->niveau = $niveau;

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
     * Set the value of dose.
     */
    public function setDose(string $dose): static
    {
        $this->dose = $dose;

        return $this;
    }

    /**
     * Get the value of dose.
     */
    public function getDose(): string
    {
        return $this->dose;
    }

    /**
     * Add GroupeHasIngredient entity to collection (one to many).
     */
    public function addGroupeHasIngredient(GroupeHasIngredient $groupeHasIngredient): static
    {
        $this->groupeHasIngredients[] = $groupeHasIngredient;

        return $this;
    }

    /**
     * Remove GroupeHasIngredient entity from collection (one to many).
     */
    public function removeGroupeHasIngredient(GroupeHasIngredient $groupeHasIngredient): static
    {
        $this->groupeHasIngredients->removeElement($groupeHasIngredient);

        return $this;
    }

    /**
     * Get GroupeHasIngredient entity collection (one to many).
     */
    public function getGroupeHasIngredients(): Collection
    {
        return $this->groupeHasIngredients;
    }

    /**
     * Add PersonnageIngredient entity to collection (one to many).
     */
    public function addPersonnageIngredient(PersonnageIngredient $personnageIngredient): static
    {
        $this->personnageIngredients[] = $personnageIngredient;

        return $this;
    }

    /**
     * Remove PersonnageIngredient entity from collection (one to many).
     */
    public function removePersonnageIngredient(PersonnageIngredient $personnageIngredient): static
    {
        $this->personnageIngredients->removeElement($personnageIngredient);

        return $this;
    }

    /**
     * Get PersonnageIngredient entity collection (one to many).
     */
    public function getPersonnageIngredients(): Collection
    {
        return $this->personnageIngredients;
    }

    /**
     * Add Territoire entity to collection.
     */
    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection.
     */
    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection.
     */
    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'niveau', 'dose'];
    }
}
