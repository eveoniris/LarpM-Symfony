<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Etat.
 *
 * @Table(name="etat")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseEtat", "extended":"Etat"})
 */
class BaseEtat
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
     * @OneToMany(targetEntity="Objet", mappedBy="etat")
     *
     * @JoinColumn(name="id", referencedColumnName="etat_id", nullable=false)
     */
    protected $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Etat
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
     * @return \App\Entity\Etat
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
     * Add Objet entity to collection (one to many).
     *
     * @return \App\Entity\Etat
     */
    public function addObjet(Objet $objet)
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     *
     * @return \App\Entity\Etat
     */
    public function removeObjet(Objet $objet)
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getObjets()
    {
        return $this->objets;
    }

    public function __sleep()
    {
        return ['id', 'label'];
    }
}