<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Culture.
 *
 * @Table(name="culture")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseCulture", "extended":"Culture"})
 */
class BaseCulture
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
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text")
     */
    protected $description;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_complete;

    /**
     * @OneToMany(targetEntity="Territoire", mappedBy="culture")
     *
     * @JoinColumn(name="id", referencedColumnName="culture_id", nullable=false)
     */
    protected $territoires;

    public function __construct()
    {
        $this->territoires = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Culture
     */
    public function setId($id): static
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
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Culture
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
     *
     * @return \App\Entity\Culture
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
     * Set the value of description_complete.
     *
     * @param string $description_complete
     *
     * @return \App\Entity\Culture
     */
    public function setDescriptionComplete($description_complete)
    {
        $this->description_complete = $description_complete;

        return $this;
    }

    /**
     * Get the value of description_complete.
     *
     * @return string
     */
    public function getDescriptionComplete()
    {
        return $this->description_complete;
    }

    /**
     * Add Territoire entity to collection (one to many).
     *
     * @return \App\Entity\Culture
     */
    public function addTerritoire(Territoire $territoire)
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     *
     * @return \App\Entity\Culture
     */
    public function removeTerritoire(Territoire $territoire)
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoires()
    {
        return $this->territoires;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'description_complete'];
    }
}