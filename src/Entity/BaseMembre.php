<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'membre')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_groupe_secondaire_personnage1_idx')]
#[ORM\Index(columns: ['secondary_group_id'], name: 'fk_personnage_groupe_secondaire_secondary_group1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseMembre', 'extended' => 'Membre'])]
abstract class BaseMembre
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected bool $secret = false;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'membres')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: SecondaryGroup::class, inversedBy: 'membres')]
    #[JoinColumn(name: 'secondary_group_id', referencedColumnName: 'id', nullable: 'false')]
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
     * Set the value of secret.
     */
    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     */
    public function getSecret(): bool
    {
        return $this->secret;
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

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'secondary_group_id', 'secret'];
    } */
}
