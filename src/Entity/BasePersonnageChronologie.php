<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnages_chronologie')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageChronologie', 'extended' => 'PersonnageChronologie'])]
abstract class BasePersonnageChronologie
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageChronologie')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $evenement = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $annee = null;

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
     * Set the value of evenement.
     */
    public function setEvenement(string $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Get the value of evenement.
     */
    public function getEvenement(): string
    {
        return $this->evenement;
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
     * Set the value of annee.
     */
    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    /**
     * Get the value of annee.
     */
    public function getAnnee(): int
    {
        return $this->annee;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'evenement', 'annee'];
    } */
}
