<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Rarete.
 *
 * @Table(name="rarete")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseRarete", "extended":"Rarete"})
 */
class BaseRarete
{
    /**
     * @Id
     *
     * @Column(type="integer")
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(name="`value`", type="integer")
     */
    protected $value;

    /**
     * @OneToMany(targetEntity="Ressource", mappedBy="rarete")
     *
     * @JoinColumn(name="id", referencedColumnName="rarete_id", nullable=false)
     */
    protected $ressources;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Rarete
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
     * @return \App\Entity\Rarete
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
     * Set the value of value.
     *
     * @param int $value
     *
     * @return \App\Entity\Rarete
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Add Ressource entity to collection (one to many).
     *
     * @return \App\Entity\Rarete
     */
    public function addRessource(Ressource $ressource)
    {
        $this->ressources[] = $ressource;

        return $this;
    }

    /**
     * Remove Ressource entity from collection (one to many).
     *
     * @return \App\Entity\Rarete
     */
    public function removeRessource(Ressource $ressource)
    {
        $this->ressources->removeElement($ressource);

        return $this;
    }

    /**
     * Get Ressource entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRessources()
    {
        return $this->ressources;
    }

    public function __sleep()
    {
        return ['id', 'label', 'value'];
    }
}