<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'participant_has_restauration')]
#[ORM\Index(columns: ['participant_id'], name: 'fk_participant_has_restauration_participant1_idx')]
#[ORM\Index(columns: ['restauration_id'], name: 'fk_participant_has_restauration_restauration1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseParticipantHasRestauration', 'extended' => 'ParticipantHasRestauration'])]
abstract class BaseParticipantHasRestauration
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $date;

    #[ManyToOne(targetEntity: Participant::class, inversedBy: 'participantHasRestaurations', cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: 'false')]
    protected Participant $participant;

    #[ManyToOne(targetEntity: Restauration::class, inversedBy: 'participantHasRestaurations', cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'restauration_id', referencedColumnName: 'id', nullable: 'false')]
    protected Restauration $restauration;

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of date.
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set Participant entity (many to one).
     */
    public function setParticipant(Participant $participant = null): static
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     */
    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    /**
     * Set Restauration entity (many to one).
     */
    public function setRestauration(Restauration $restauration = null): static
    {
        $this->restauration = $restauration;

        return $this;
    }

    /**
     * Get Restauration entity (many to one).
     */
    public function getRestauration(): Restauration
    {
        return $this->restauration;
    }

    /* public function __sleep()
    {
        return ['id', 'participant_id', 'restauration_id', 'date'];
    } */
}
