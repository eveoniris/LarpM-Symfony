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
#[ORM\Table(name: 'quality')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseQuality', 'extended' => 'Quality'])]
abstract class BaseQuality
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $numero = null;

    #[OneToMany(mappedBy: 'quality', targetEntity: Item::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'quality_id', nullable: 'false')]
    protected Collection $items;

    #[OneToMany(mappedBy: 'quality', targetEntity: QualityValeur::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'quality_id', nullable: 'false')]
    protected Collection $qualityValeurs;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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
     * Set the value of numero.
     */
    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     */
    public function getNumero(): ?int
    {
        return $this->numero;
    }

    /**
     * Add Item entity to collection (one to many).
     */
    public function addItem(Item $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection (one to many).
     */
    public function removeItem(Item $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection (one to many).
     */
    public function getItems(): Collection
    {
        return $this->items;
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
        return ['id', 'label', 'numero'];
    } */
}
