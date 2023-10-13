<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Age
 *
 * @Table(name="age")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BaseAge", "extended":"Age"})
 */
class BaseAge
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=100)
     */
    protected $label;

    /**
     * @Column(type="string", length=450, nullable=true)
     */
    protected $description;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $bonus;

    /**
     * @Column(type="boolean")
     */
    protected $enableCreation;

    /**
     * @Column(type="integer")
     */
    protected $minimumValue;

    /**
     * @OneToMany(targetEntity="Personnage", mappedBy="age")
     * @JoinColumn(name="id", referencedColumnName="age_id", nullable=false)
     */
    protected $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\Age
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
     * Set the value of label.
     *
     * @param string $label
     * @return \App\Entity\Age
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     * @return \App\Entity\Age
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of bonus.
     *
     * @param integer $bonus
     * @return \App\Entity\Age
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get the value of bonus.
     *
     * @return integer
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set the value of enableCreation.
     *
     * @param boolean $enableCreation
     * @return \App\Entity\Age
     */
    public function setEnableCreation($enableCreation)
    {
        $this->enableCreation = $enableCreation;

        return $this;
    }

    /**
     * Get the value of enableCreation.
     *
     * @return boolean
     */
    public function getEnableCreation()
    {
        return $this->enableCreation;
    }

    /**
     * Set the value of minimumValue.
     *
     * @param integer $minimumValue
     * @return \App\Entity\Age
     */
    public function setMinimumValue($minimumValue)
    {
        $this->minimumValue = $minimumValue;

        return $this;
    }

    /**
     * Get the value of minimumValue.
     *
     * @return integer
     */
    public function getMinimumValue()
    {
        return $this->minimumValue;
    }

    /**
     * Add Personnage entity to collection (one to many).
     *
     * @param \App\Entity\Personnage $personnage
     * @return \App\Entity\Age
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection (one to many).
     *
     * @param \App\Entity\Personnage $personnage
     * @return \App\Entity\Age
     */
    public function removePersonnage(Personnage $personnage)
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Get Personnage entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnages()
    {
        return $this->personnages;
    }

    public function __sleep()
    {
        return array('id', 'label', 'description', 'bonus', 'enableCreation', 'minimumValue');
    }
}