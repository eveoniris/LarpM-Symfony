<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\GroupeHasRessource.
 *
 * @Table(name="groupe_has_ressource", indexes={@Index(name="fk_groupe_has_ressource_groupe1_idx", columns={"groupe_id"}), @Index(name="fk_groupe_has_ressource_ressource1_idx", columns={"ressource_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseGroupeHasRessource", "extended":"GroupeHasRessource"})
 */
class BaseGroupeHasRessource
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="integer")
     */
    protected $quantite;

    /**
     * @ManyToOne(targetEntity="Groupe", inversedBy="groupeHasRessources", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="groupe_id", referencedColumnName="id", nullable=false)
     */
    protected $groupe;

    /**
     * @ManyToOne(targetEntity="Ressource", inversedBy="groupeHasRessources")
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
     * @return \App\Entity\GroupeHasRessource
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of quantite.
     *
     * @param int $quantite
     *
     * @return \App\Entity\GroupeHasRessource
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Get the value of quantite.
     *
     * @return int
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * Set Groupe entity (many to one).
     *
     * @return \App\Entity\GroupeHasRessource
     */
    public function setGroupe(Groupe $groupe = null)
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     *
     * @return \App\Entity\Groupe
     */
    public function getGroupe()
    {
        return $this->groupe;
    }

    /**
     * Set Ressource entity (many to one).
     *
     * @return \App\Entity\GroupeHasRessource
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
        return ['id', 'quantite', 'groupe_id', 'ressource_id'];
    }
}
