<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'groupe_has_ingredient')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_has_ingredient_groupe1_idx')]
#[ORM\Index(columns: ['ingredient_id'], name: 'fk_groupe_has_ingredient_ingredient1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeHasIngredient', 'extended' => 'GroupeHasIngredient'])]
abstract class BaseGroupeHasIngredient
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::INTEGER)]
    protected int $quantite = 0;

    #[ORM\ManyToOne(targetEntity: Groupe::class, cascade: ['persist', 'remove'], inversedBy: 'groupeHasIngredients')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id')]
    protected Groupe $groupe;

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'groupeHasIngredients')]
    #[ORM\JoinColumn(name: 'ingredient_id', referencedColumnName: 'id')]
    protected ?Ingredient   $ingredient;

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
     * Set Groupe entity (many to one).
     */
    public function setGroupe(?Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): Groupe
    {
        return $this->groupe;
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
        return ['id', 'quantite', 'groupe_id', 'ingredient_id'];
    } */
}
