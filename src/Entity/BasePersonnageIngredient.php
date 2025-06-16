<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_ingredient')]
#[ORM\Index(columns: ['ingredient_id'], name: 'fk_personnage_ingredient_ingredient1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageIngredient', 'extended' => 'PersonnageIngredient'])]
abstract class BasePersonnageIngredient
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $nombre = null;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageIngredients')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: Ingredient::class, inversedBy: 'personnageIngredients')]
    #[JoinColumn(name: 'ingredient_id', referencedColumnName: 'id', nullable: false)]
    protected Ingredient $ingredient;

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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of nombre.
     */
    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     */
    public function getNombre(): int
    {
        return $this->nombre ?? 0;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Ingredient entity (many to one).
     */
    public function setIngredient(?Ingredient $ingredient = null): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    /**
     * Get Ingredient entity (many to one).
     */
    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'ingredient_id', 'nombre'];
    } */
}
