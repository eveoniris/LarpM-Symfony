<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\HeroismeHistory.
 *
 * @Table(name="heroisme_history", indexes={@Index(name="fk_heroisme_history_personnage1_idx", columns={"personnage_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseHeroismeHistory", "extended":"HeroismeHistory"})
 */
class BaseHeroismeHistory
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`date`", type="date")
     */
    protected $date;

    /**
     * @Column(type="integer")
     */
    protected $heroisme;

    /**
     * @Column(type="text")
     */
    protected $explication;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="heroismeHistories")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\HeroismeHistory
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
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\HeroismeHistory
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of heroisme.
     *
     * @param int $heroisme
     *
     * @return \App\Entity\HeroismeHistory
     */
    public function setHeroisme($heroisme)
    {
        $this->heroisme = $heroisme;

        return $this;
    }

    /**
     * Get the value of heroisme.
     *
     * @return int
     */
    public function getHeroisme()
    {
        return $this->heroisme;
    }

    /**
     * Set the value of explication.
     *
     * @param string $explication
     *
     * @return \App\Entity\HeroismeHistory
     */
    public function setExplication($explication)
    {
        $this->explication = $explication;

        return $this;
    }

    /**
     * Get the value of explication.
     *
     * @return string
     */
    public function getExplication()
    {
        return $this->explication;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\HeroismeHistory
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

    public function __sleep()
    {
        return ['id', 'date', 'heroisme', 'explication', 'personnage_id'];
    }
}
