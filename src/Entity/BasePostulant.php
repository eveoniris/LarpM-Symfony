<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'postulant')]
#[ORM\Index(columns: ['secondary_group_id'], name: 'fk_postulant_secondary_group1_idx')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_postulant_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePostulant', 'extended' => 'Postulant'])]
abstract class BasePostulant
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $explanation;
    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected bool $waiting;

    #[ManyToOne(targetEntity: SecondaryGroup::class, inversedBy: 'postulants')]
    #[JoinColumn(name: 'secondary_group_id', referencedColumnName: 'id', nullable: 'false')]
    protected SecondaryGroup $secondaryGroup;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'postulants')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

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
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * Set the value of explanation.
     */
    public function setExplanation(string $explanation): static
    {
        $this->explanation = $explanation;

        return $this;
    }

    /**
     * Get the value of explanation.
     */
    public function getExplanation(): string
    {
        return $this->explanation ?? '';
    }

    /**
     * Set the value of waiting.
     */
    public function setWaiting(bool $waiting): static
    {
        $this->waiting = $waiting;

        return $this;
    }

    /**
     * Get the value of waiting.
     */
    public function getWaiting(): bool
    {
        return $this->waiting;
    }

    /**
     * Set SecondaryGroup entity (many to one).
     */
    public function setSecondaryGroup(SecondaryGroup $secondaryGroup = null): static
    {
        $this->secondaryGroup = $secondaryGroup;

        return $this;
    }

    /**
     * Get SecondaryGroup entity (many to one).
     */
    public function getSecondaryGroup(): ?SecondaryGroup
    {
        return $this->secondaryGroup;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    /* public function __sleep()
    {
        return ['id', 'date', 'secondary_group_id', 'personnage_id', 'explanation', 'waiting'];
    } */
}
