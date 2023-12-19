<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'intrigue_has_groupe')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_intrigue_has_groupe_groupe1_idx')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_groupe_intrigue1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasGroupe', 'extended' => 'IntrigueHasGroupe'])]
abstract class BaseIntrigueHasGroupe
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasGroupes')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id')]
    protected Intrigue $intrigue;

    #[ORM\ManyToOne(targetEntity: Groupe::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasGroupes')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id')]
    protected Groupe $groupe;

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
     * Set Groupe entity (many to one).
     */
    public function setGroupe(Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): Groupe
    {
        return $this->groupe;
    }

    /* public function __sleep()
    {
        return ['id', 'intrigue_id', 'groupe_id'];
    } */
}
