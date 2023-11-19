<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageIngredient.
 *
 * @Table(name="personnage_ingredient", indexes={@Index(name="fk_personnage_ingredient_ingredient1_idx", columns={"ingredient_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageIngredient", "extended":"PersonnageIngredient"})
 */
class BasePersonnageIngredient
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $nombre;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageIngredients", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Ingredient", inversedBy="personnageIngredients")
     *
     * @JoinColumn(name="ingredient_id", referencedColumnName="id", nullable=false)
     */
    protected $ingredient;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageIngredient
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of nombre.
     *
     * @param int $nombre
     *
     * @return \App\Entity\PersonnageIngredient
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     *
     * @return int
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageIngredient
     */
    public function setPersonnage(Personnage $personnage = null)
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getPersonnage()
    {
        return $this->personnage;
    }

    /**
     * Set Ingredient entity (many to one).
     *
     * @return \App\Entity\PersonnageIngredient
     */
    public function setIngredient(Ingredient $ingredient = null)
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    /**
     * Get Ingredient entity (many to one).
     *
     * @return \App\Entity\Ingredient
     */
    public function getIngredient()
    {
        return $this->ingredient;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'ingredient_id', 'nombre'];
    }
}
