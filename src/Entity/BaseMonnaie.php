<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'monnaie')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseMonnaie', 'extended' => 'Monnaie'])]
abstract class BaseMonnaie
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $description;

    #[OneToMany(mappedBy: 'monnaie', targetEntity: QualityValeur::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'monnaie_id', nullable: false)]
    protected Collection $qualityValeurs;

    public function __construct()
    {
        $this->qualityValeurs = new ArrayCollection();
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
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Add QualityValeur entity to collection (one to many).
     */
    public function addQualityValeur(QualityValeur $qualityValeur): static
    {
        $this->qualityValeurs[] = $qualityValeur;

        return $this;
    }

    /**
     * Remove QualityValeur entity from collection (one to many).
     */
    public function removeQualityValeur(QualityValeur $qualityValeur): static
    {
        $this->qualityValeurs->removeElement($qualityValeur);

        return $this;
    }

    /**
     * Get QualityValeur entity collection (one to many).
     */
    public function getQualityValeurs(): Collection
    {
        return $this->qualityValeurs;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description'];
    } */
}
