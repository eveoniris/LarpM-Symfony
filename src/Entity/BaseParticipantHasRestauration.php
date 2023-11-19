<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\ParticipantHasRestauration.
 *
 * @Table(name="participant_has_restauration", indexes={@Index(name="fk_participant_has_restauration_participant1_idx", columns={"participant_id"}), @Index(name="fk_participant_has_restauration_restauration1_idx", columns={"restauration_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseParticipantHasRestauration", "extended":"ParticipantHasRestauration"})
 */
class BaseParticipantHasRestauration
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`date`", type="datetime")
     */
    protected $date;

    /**
     * @ManyToOne(targetEntity="Participant", inversedBy="participantHasRestaurations", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="participant_id", referencedColumnName="id", nullable=false)
     */
    protected $participant;

    /**
     * @ManyToOne(targetEntity="Restauration", inversedBy="participantHasRestaurations")
     *
     * @JoinColumn(name="restauration_id", referencedColumnName="id", nullable=false)
     */
    protected $restauration;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\ParticipantHasRestauration
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of date.
     *
     * @param \DateTime $date
     *
     * @return \App\Entity\ParticipantHasRestauration
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set Participant entity (many to one).
     *
     * @return \App\Entity\ParticipantHasRestauration
     */
    public function setParticipant(Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     *
     * @return \App\Entity\Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set Restauration entity (many to one).
     *
     * @return \App\Entity\ParticipantHasRestauration
     */
    public function setRestauration(Restauration $restauration = null)
    {
        $this->restauration = $restauration;

        return $this;
    }

    /**
     * Get Restauration entity (many to one).
     *
     * @return \App\Entity\Restauration
     */
    public function getRestauration()
    {
        return $this->restauration;
    }

    public function __sleep()
    {
        return ['id', 'participant_id', 'restauration_id', 'date'];
    }
}
