<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

/**
 * App\Entity\PersonnageChronologie
 *
 * @Table(name="personnages_chronologie")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BasePersonnageChronologie", "extended":"PersonnageChronologie"})
 */
class BasePersonnageChronologie
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="PersonnageChronologie")
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $evenement;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $annee;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\PersonnageChronologie
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of evenement.
     *
     * @param string $evenement
     * @return \App\Entity\PersonnageChronologie
     */
    public function setEvenement($evenement)
    {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Get the value of evenement.
     *
     * @return string
     */
    public function getEvenement()
    {
        return $this->evenement;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @param \App\Entity\Personnage $personnage
     * @return \App\Entity\PersonnageChronologie
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
     * Set the value of annee.
     *
     * @param integer $annee
     * @return \App\Entity\PersonnageChronologie
     */
    public function setAnnee($annee)
    {
        $this->annee = $annee;

        return $this;
    }

    /**
     * Get the value of annee.
     *
     * @return integer
     */
    public function getAnnee()
    {
        return $this->annee;
    }
    
    public function __sleep()
    {
        return array('id', 'personnage_id', 'evenement', 'annee');
    }
}