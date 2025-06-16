<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnages_lignee')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageLignee', 'extended' => 'PersonnageLignee'])]
abstract class BasePersonnageLignee
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageLignee')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'PersonnageLigneeParent1')]
    #[JoinColumn(name: 'parent1_id', referencedColumnName: 'id', nullable: false)]
    protected ?Personnage $parent1;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'PersonnageLigneeParent2')]
    #[JoinColumn(name: 'parent2_id', referencedColumnName: 'id', nullable: false)]
    protected ?Personnage $parent2;

    #[ManyToOne(targetEntity: Lignee::class, inversedBy: 'personnageLignees')]
    #[JoinColumn(name: 'lignee_id', referencedColumnName: 'id', nullable: false)]
    protected ?Lignee $lignee;

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
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Parent1 entity (many to one).
     */
    public function setParent1(?Personnage $parent1 = null): static
    {
        $this->parent1 = $parent1;

        return $this;
    }

    /**
     * Get Parent1 entity (many to one).
     */
    public function getParent1(): ?Personnage
    {
        return $this->parent1;
    }

    /**
     * Set Parent2 entity (many to one).
     */
    public function setParent2(?Personnage $parent2 = null): static
    {
        $this->parent2 = $parent2;

        return $this;
    }

    /**
     * Get Parent2 entity (many to one).
     */
    public function getParent2(): ?Personnage
    {
        return $this->parent2;
    }

    /**
     * Set Lignee entity (many to one).
     */
    public function setLignee(?Lignee $lignee = null): static
    {
        $this->lignee = $lignee;

        return $this;
    }

    /**
     * Get Lignee entity (many to one).
     */
    public function getLignee(): ?Lignee
    {
        return $this->lignee ?? null;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'parent1_id', 'parent2_id', 'ligne_id'];
    } */
}
