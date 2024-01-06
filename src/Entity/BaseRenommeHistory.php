<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'renomme_history')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_renomme_history_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRenommeHistory', 'extended' => 'RenommeHistory'])]
abstract class BaseRenommeHistory
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $renomme = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $explication = '';

    #[Column(name: 'date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected \DateTime $date;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'renommeHistories')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    public function __construct()
    {
        $this->setDate(new \DateTime('NOW'));
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
     * Set the value of renomme.
     */
    public function setRenomme(int $renomme): static
    {
        $this->renomme = $renomme;

        return $this;
    }

    /**
     * Get the value of renomme.
     */
    public function getRenomme(): int
    {
        return $this->renomme;
    }

    /**
     * Set the value of explication.
     */
    public function setExplication(string $explication): static
    {
        $this->explication = $explication;

        return $this;
    }

    /**
     * Get the value of explication.
     */
    public function getExplication(): ?string
    {
        return $this->explication;
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
        return ['id', 'renomme', 'explication', 'date', 'personnage_id'];
    } */
}
