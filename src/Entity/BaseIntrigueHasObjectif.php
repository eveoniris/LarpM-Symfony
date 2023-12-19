<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'intrigue_has_objectif')]
#[ORM\Index(columns: ['objectif_id'], name: 'fk_intrigue_has_objectif_objectif1_idx')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_objectif_intrigue1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasObjectif', 'extended' => 'IntrigueHasObjectif'])]
abstract class BaseIntrigueHasObjectif
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasObjectifs')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    #[ORM\ManyToOne(targetEntity: Objectif::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasObjectifs')]
    #[ORM\JoinColumn(name: 'objectif_id', referencedColumnName: 'id', nullable: false)]
    protected Objectif $objectif;

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
     * Set Objectif entity (many to one).
     */
    public function setObjectif(Objectif $objectif = null): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    /**
     * Get Objectif entity (many to one).
     */
    public function getObjectif(): Objectif
    {
        return $this->objectif;
    }

    /* public function __sleep()
    {
        return ['id', 'intrigue_id', 'objectif_id'];
    } */
}
