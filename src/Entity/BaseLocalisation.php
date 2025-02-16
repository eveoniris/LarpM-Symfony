<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'localisation')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseLocalisation', 'extended' => 'Localisation'])]
abstract class BaseLocalisation
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected string $label;

    #[Column(name: '`precision`', type: Types::STRING, length: 450)]
    protected ?string $precision = null;

    #[OneToMany(mappedBy: 'localisation', targetEntity: Rangement::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'localisation_id', nullable: 'false')]
    protected Collection $rangements;

    public function __construct()
    {
        $this->rangements = new ArrayCollection();
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
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of precision.
     *
     * @param string $precision
     */
    public function setPrecision($precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Get the value of precision.
     */
    public function getPrecision(): string
    {
        return $this->precision ?? '';
    }

    /**
     * Add Rangement entity to collection (one to many).
     */
    public function addRangement(Rangement $rangement): static
    {
        $this->rangements[] = $rangement;

        return $this;
    }

    /**
     * Remove Rangement entity from collection (one to many).
     */
    public function removeRangement(Rangement $rangement): static
    {
        $this->rangements->removeElement($rangement);

        return $this;
    }

    /**
     * Get Rangement entity collection (one to many).
     */
    public function getRangements(): Collection
    {
        return $this->rangements;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'precision'];
    } */
}
