<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'objectif')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseObjectif', 'extended' => 'Objectif'])]
class BaseObjectif
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    #[Column(name: '`text`', type: 'string', length: 450)]
    protected string $text;

    #[Column(type: 'datetime')]
    protected \DateTime $date_creation;

    #[Column(type: 'datetime')]
    protected \DateTime $date_update;

    /**
     * @OneToMany(targetEntity="IntrigueHasObjectif", mappedBy="objectif", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="id", referencedColumnName="objectif_id", nullable=false)
     */
    protected $intrigueHasObjectifs;

    public function __construct()
    {
        $this->intrigueHasObjectifs = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Objectif
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
     * Set the value of text.
     *
     * @param string $text
     *
     * @return \App\Entity\Objectif
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value of date_creation.
     *
     * @param \DateTime $date_creation
     *
     * @return \App\Entity\Objectif
     */
    public function setDateCreation($date_creation)
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    /**
     * Get the value of date_creation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->date_creation;
    }

    /**
     * Set the value of date_update.
     *
     * @param \DateTime $date_update
     *
     * @return \App\Entity\Objectif
     */
    public function setDateUpdate($date_update)
    {
        $this->date_update = $date_update;

        return $this;
    }

    /**
     * Get the value of date_update.
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->date_update;
    }

    /**
     * Add IntrigueHasObjectif entity to collection (one to many).
     *
     * @return \App\Entity\Objectif
     */
    public function addIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif)
    {
        $this->intrigueHasObjectifs[] = $intrigueHasObjectif;

        return $this;
    }

    /**
     * Remove IntrigueHasObjectif entity from collection (one to many).
     *
     * @return \App\Entity\Objectif
     */
    public function removeIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif)
    {
        $this->intrigueHasObjectifs->removeElement($intrigueHasObjectif);

        return $this;
    }

    /**
     * Get IntrigueHasObjectif entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIntrigueHasObjectifs()
    {
        return $this->intrigueHasObjectifs;
    }

    public function __sleep()
    {
        return ['id', 'text', 'date_creation', 'date_update'];
    }
}