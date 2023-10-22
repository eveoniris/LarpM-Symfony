<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Restauration.
 *
 * @Table(name="restauration")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseRestauration", "extended":"Restauration"})
 */
class BaseRestauration
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="ParticipantHasRestauration", mappedBy="restauration")
     *
     * @JoinColumn(name="id", referencedColumnName="restauration_id", nullable=false)
     */
    protected $participantHasRestaurations;

    public function __construct()
    {
        $this->participantHasRestaurations = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Restauration
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
     * @return \App\Entity\Restauration
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
     * @return \App\Entity\Restauration
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
     * Add ParticipantHasRestauration entity to collection (one to many).
     *
     * @return \App\Entity\Restauration
     */
    public function addParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration)
    {
        $this->participantHasRestaurations[] = $participantHasRestauration;

        return $this;
    }

    /**
     * Remove ParticipantHasRestauration entity from collection (one to many).
     *
     * @return \App\Entity\Restauration
     */
    public function removeParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration)
    {
        $this->participantHasRestaurations->removeElement($participantHasRestauration);

        return $this;
    }

    /**
     * Get ParticipantHasRestauration entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipantHasRestaurations()
    {
        return $this->participantHasRestaurations;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description'];
    }
}
