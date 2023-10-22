<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\CultureHasClasse.
 *
 * @Table(name="culture_has_classe", indexes={@Index(name="fk_culture_has_classe_culture1_idx", columns={"culture_id"}), @Index(name="fk_culture_has_classe_classe1_idx", columns={"classe_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseCultureHasClasse", "extended":"CultureHasClasse"})
 */
class BaseCultureHasClasse
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Culture", inversedBy="cultureHasClasses")
     *
     * @JoinColumn(name="culture_id", referencedColumnName="id", nullable=false)
     */
    protected $culture;

    /**
     * @ManyToOne(targetEntity="Classe", inversedBy="cultureHasClasses")
     *
     * @JoinColumn(name="classe_id", referencedColumnName="id", nullable=false)
     */
    protected $classe;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\CultureHasClasse
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
     * Set Culture entity (many to one).
     *
     * @return \App\Entity\CultureHasClasse
     */
    public function setCulture(Culture $culture = null)
    {
        $this->culture = $culture;

        return $this;
    }

    /**
     * Get Culture entity (many to one).
     *
     * @return \App\Entity\Culture
     */
    public function getCulture()
    {
        return $this->culture;
    }

    /**
     * Set Classe entity (many to one).
     *
     * @return \App\Entity\CultureHasClasse
     */
    public function setClasse(Classe $classe = null)
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * Get Classe entity (many to one).
     *
     * @return \App\Entity\Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    public function __sleep()
    {
        return ['id', 'culture_id', 'classe_id'];
    }
}
