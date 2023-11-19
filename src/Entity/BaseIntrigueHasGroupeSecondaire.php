<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'intrigue_has_groupe_secondaire')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_groupe_secondaire_intrigue1_idx')]
#[ORM\Index(columns: ['secondary_group_id'], name: 'fk_intrigue_has_groupe_secondaire_secondary_group1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasGroupeSecondaire', 'extended' => 'IntrigueHasGroupeSecondaire'])]
abstract class BaseIntrigueHasGroupeSecondaire
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasGroupeSecondaires')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    #[ORM\ManyToOne(targetEntity: SecondaryGroup::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasGroupeSecondaires')]
    #[ORM\JoinColumn(name: 'secondary_group_id', referencedColumnName: 'id', nullable: false)]
    protected SecondaryGroup $secondaryGroup;

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
     * Set Intrigue entity (many to one).
     */
    public function setIntrigue(Intrigue $intrigue = null): static
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Intrigue entity (many to one).
     */
    public function getIntrigue(): Intrigue
    {
        return $this->intrigue;
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
    public function getSecondaryGroup(): SecondaryGroup
    {
        return $this->secondaryGroup;
    }

    public function __sleep()
    {
        return ['id', 'intrigue_id', 'secondary_group_id'];
    }
}
