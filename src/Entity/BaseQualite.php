<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Qualite.
 *
 * @Table(name="qualite")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseQualite", "extended":"Qualite"})
 */
class BaseQualite
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $numero;

    /**
     * @OneToMany(targetEntity="Item", mappedBy="qualite")
     *
     * @JoinColumn(name="id", referencedColumnName="qualite_id", nullable=false)
     */
    protected $items;

    /**
     * @OneToMany(targetEntity="QualiteValeur", mappedBy="qualite")
     *
     * @JoinColumn(name="id", referencedColumnName="qualite_id", nullable=false)
     */
    protected $qualiteValeurs;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->qualiteValeurs = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Qualite
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
     * @return \App\Entity\Qualite
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
     * Set the value of numero.
     *
     * @param int $numero
     *
     * @return \App\Entity\Qualite
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     *
     * @return int
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Add Item entity to collection (one to many).
     *
     * @return \App\Entity\Qualite
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection (one to many).
     *
     * @return \App\Entity\Qualite
     */
    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add QualiteValeur entity to collection (one to many).
     *
     * @return \App\Entity\Qualite
     */
    public function addQualiteValeur(QualiteValeur $qualiteValeur)
    {
        $this->qualiteValeurs[] = $qualiteValeur;

        return $this;
    }

    /**
     * Remove QualiteValeur entity from collection (one to many).
     *
     * @return \App\Entity\Qualite
     */
    public function removeQualiteValeur(QualiteValeur $qualiteValeur)
    {
        $this->qualiteValeurs->removeElement($qualiteValeur);

        return $this;
    }

    /**
     * Get QualiteValeur entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQualiteValeurs()
    {
        return $this->qualiteValeurs;
    }

    public function __sleep()
    {
        return ['id', 'label', 'numero'];
    }
}
