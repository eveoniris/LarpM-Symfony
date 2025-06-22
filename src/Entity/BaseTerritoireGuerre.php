<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity]
#[ORM\Table(name: 'territoire_guerre')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTerritoireGuerre', 'extended' => 'TerritoireGuerre'])]
abstract class BaseTerritoireGuerre
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $puissance;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $puissance_max;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $protection = null;

    #[ORM\OneToOne(mappedBy: 'territoireGuerre', targetEntity: Territoire::class)]
    protected Territoire $territoire;

    public function __construct()
    {
        // $this->territoires = new ArrayCollection();
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
     * Get the value of protection.
     */
    public function getProtection(): int
    {
        return $this->protection;
    }

    /**
     * Set the value of protection.
     *
     * @param int $protection
     */
    public function setProtection($protection): static
    {
        $this->protection = $protection;

        return $this;
    }

    /**
     * Get the value of puissance.
     */
    public function getPuissance(): int
    {
        return $this->puissance;
    }

    /**
     * Set the value of puissance.
     */
    public function setPuissance(int $puissance): static
    {
        $this->puissance = $puissance;

        return $this;
    }

    /**
     * Get the value of puissance_max.
     */
    public function getPuissanceMax(): int
    {
        return $this->puissance_max;
    }

    /**
     * Set the value of puissance_max.
     */
    public function setPuissanceMax(int $puissance_max): static
    {
        $this->puissance_max = $puissance_max;

        return $this;
    }

    /**
     * Get Territoire entity (one to one).
     */
    public function getTerritoire(): Territoire
    {
        return $this->territoire;
    }

    /**
     * Set Territoire entity (one to one).
     */
    public function setTerritoire(?Territoire $territoire = null): static
    {
        $territoire?->setTerritoireGuerre($this);
        $this->territoire = $territoire;

        return $this;
    }

    /* public function __sleep()
    {
        return ['id', 'puissance', 'puissance_max', 'protection'];
    } */
}
