<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\ReligionLevel.
 *
 * @Table(name="religion_level")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseReligionLevel", "extended":"ReligionLevel"})
 */
class BaseReligionLevel
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;
    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(name="`index`", type="integer")
     */
    protected $index;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="PersonnagesReligions", mappedBy="religionLevel")
     *
     * @JoinColumn(name="id", referencedColumnName="religion_level_id", nullable=false)
     */
    protected $personnagesReligions;

    public function __construct()
    {
        $this->personnagesReligions = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\ReligionLevel
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
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\ReligionLevel
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
     * Set the value of index.
     *
     * @param int $index
     *
     * @return \App\Entity\ReligionLevel
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get the value of index.
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\ReligionLevel
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
     * Add PersonnagesReligions entity to collection (one to many).
     *
     * @return \App\Entity\ReligionLevel
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     *
     * @return \App\Entity\ReligionLevel
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnagesReligions()
    {
        return $this->personnagesReligions;
    }

    public function __sleep()
    {
        return ['id', 'label', 'index', 'description'];
    }
}
