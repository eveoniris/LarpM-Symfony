<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'heroisme_history')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_heroisme_history_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseHeroismeHistory', 'extended' => 'HeroismeHistory'])]
abstract class BaseHeroismeHistory
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected DateTime $date;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $heroisme;

    #[ORM\Column(type: Types::STRING)]
    protected string $explication;

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'heroismeHistories')]
    #[ORM\JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected $personnage;

    public function __construct()
    {
    }

    /**
     * Get the value of date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the value of date.
     */
    public function setDate(DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of explication.
     */
    public function getExplication(): string
    {
        return $this->explication;
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
     * Get the value of heroisme.
     */
    public function getHeroisme(): int
    {
        return $this->heroisme;
    }

    /**
     * Set the value of heroisme.
     */
    public function setHeroisme(int $heroisme): static
    {
        $this->heroisme = $heroisme;

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
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

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
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /* public function __sleep()
    {
        return ['id', 'date', 'heroisme', 'explication', 'personnage_id'];
    } */
}
