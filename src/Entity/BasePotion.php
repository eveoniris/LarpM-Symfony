<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'potion')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePotion', 'extended' => 'Potion'])]
abstract class BasePotion
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $niveau;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $numero = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $secret = null;

    #[ORM\ManyToMany(targetEntity: Personnage::class, inversedBy: 'potions')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
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
     * Set the value of documentUrl.
     */
    public function setDocumentUrl(string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of documentUrl.
     */
    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    /**
     * Set the value of niveau.
     */
    public function setNiveau(int $niveau): string
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * Get the value of niveau.
     */
    public function getNiveau(): int
    {
        return $this->niveau;
    }

    /**
     * Set the value of numero.
     */
    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     */
    public function getNumero(): string
    {
        return $this->numero ?? '';
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
     * Add Personnage entity to collection.
     */
    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Get Personnage entity collection.
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'documentUrl', 'niveau', 'numero', 'secret'];
    }
}
