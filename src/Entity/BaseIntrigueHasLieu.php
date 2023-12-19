<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'intrigue_has_lieu')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_lieu_intrigue1_idx')]
#[ORM\Index(columns: ['lieu_id'], name: 'fk_intrigue_has_lieu_lieu1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasLieu', 'extended' => 'IntrigueHasLieu'])]
abstract class BaseIntrigueHasLieu
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasLieus')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    #[ORM\ManyToOne(targetEntity: Lieu::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasLieus')]
    #[ORM\JoinColumn(name: 'lieu_id', referencedColumnName: 'id', nullable: false)]
    protected Lieu $lieu;

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
     * Set Lieu entity (many to one).
     */
    public function setLieu(Lieu $lieu = null): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * Get Lieu entity (many to one).
     */
    public function getLieu(): Lieu
    {
        return $this->lieu;
    }

    /* public function __sleep()
    {
        return ['id', 'intrigue_id', 'lieu_id'];
    } */
}
