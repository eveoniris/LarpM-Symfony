<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageRessource.
 *
 * @Table(name="personnage_ressource", indexes={@Index(name="fk_personnage_ressource_ressource1_idx", columns={"ressource_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageRessource", "extended":"PersonnageRessource"})
 */
class BasePersonnageRessource
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $nombre;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageRessources", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Ressource", inversedBy="personnageRessources")
     *
     * @JoinColumn(name="ressource_id", referencedColumnName="id", nullable=false)
     */
    protected $ressource;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageRessource
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
     * @return \App\Entity\PersonnageRessource
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
     * @return \App\Entity\PersonnageRessource
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
     * Set Ressource entity (many to one).
     *
     * @return \App\Entity\PersonnageRessource
     */
    public function setRessource(Ressource $ressource = null)
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get Ressource entity (many to one).
     *
     * @return \App\Entity\Ressource
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'ressource_id', 'nombre'];
    }
}
