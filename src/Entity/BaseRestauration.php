<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'restauration')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRestauration', 'extended' => 'Restauration'])]
abstract class BaseRestauration
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected ?string $label = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[OneToMany(mappedBy: 'restauration', targetEntity: ParticipantHasRestauration::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'restauration_id', nullable: 'false')]
    protected ArrayCollection $participantHasRestaurations;

    public function __construct()
    {
        $this->participantHasRestaurations = new ArrayCollection();
    }

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
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Add ParticipantHasRestauration entity to collection (one to many).
     */
    public function addParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration): static
    {
        $this->participantHasRestaurations[] = $participantHasRestauration;

        return $this;
    }

    /**
     * Remove ParticipantHasRestauration entity from collection (one to many).
     */
    public function removeParticipantHasRestauration(ParticipantHasRestauration $participantHasRestauration): static
    {
        $this->participantHasRestaurations->removeElement($participantHasRestauration);

        return $this;
    }

    /**
     * Get ParticipantHasRestauration entity collection (one to many).
     */
    public function getParticipantHasRestaurations(): ArrayCollection
    {
        return $this->participantHasRestaurations;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description'];
    }
}
