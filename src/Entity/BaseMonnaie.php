<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Monnaie.
 *
 * @Table(name="monnaie")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseMonnaie", "extended":"Monnaie"})
 */
class BaseMonnaie
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text")
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="QualityValeur", mappedBy="monnaie")
     *
     * @JoinColumn(name="id", referencedColumnName="monnaie_id", nullable=false)
     */
    protected $qualityValeurs;

    public function __construct()
    {
        $this->qualityValeurs = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Monnaie
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Monnaie
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Monnaie
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
     * Add QualityValeur entity to collection (one to many).
     *
     * @return \App\Entity\Monnaie
     */
    public function addQualityValeur(QualityValeur $qualityValeur)
    {
        $this->qualityValeurs[] = $qualityValeur;

        return $this;
    }

    /**
     * Remove QualityValeur entity from collection (one to many).
     *
     * @return \App\Entity\Monnaie
     */
    public function removeQualityValeur(QualityValeur $qualityValeur)
    {
        $this->qualityValeurs->removeElement($qualityValeur);

        return $this;
    }

    /**
     * Get QualityValeur entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQualityValeurs()
    {
        return $this->qualityValeurs;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description'];
    }
}
