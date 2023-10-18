<?php

namespace App\Entity;

/**
 * App\Entity\RenommeHistory.
 *
 * @Table(name="renomme_history", indexes={@Index(name="fk_renomme_history_personnage1_idx", columns={"personnage_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseRenommeHistory", "extended":"RenommeHistory"})
 */
class BaseRenommeHistory
{
    /**
     * @Id
     *
     * @Column(type="integer", options={"unsigned":true})
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="integer")
     */
    protected $renomme;

    /**
     * @Column(type="text")
     */
    protected $explication;

    /**
     * @Column(name="`date`", type="datetime")
     */
    protected $date;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="renommeHistories")
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
     * @return \App\Entity\RenommeHistory
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
     * Set the value of renomme.
     *
     * @param int $renomme
     *
     * @return \App\Entity\RenommeHistory
     */
    public function setRenomme($renomme)
    {
        $this->renomme = $renomme;

        return $this;
    }

    /**
     * Get the value of renomme.
     *
     * @return int
     */
    public function getRenomme()
    {
        return $this->renomme;
    }

    /**
     * Set the value of explication.
     *
     * @param string $explication
     *
     * @return \App\Entity\RenommeHistory
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
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\RenommeHistory
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
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\RenommeHistory
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
        return ['id', 'renomme', 'explication', 'date', 'personnage_id'];
    }
}