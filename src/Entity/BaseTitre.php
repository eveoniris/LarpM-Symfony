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
#[ORM\Table(name: 'titre')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTitre', 'extended' => 'Titre'])]
abstract class BaseTitre
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $renomme;

    #[OneToMany(mappedBy: 'titre', targetEntity: TitreTerritoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'titre_id', nullable: 'false')]
    protected Collection $titreTerritoires;

    public function __construct()
    {
        $this->titreTerritoires = new ArrayCollection();
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
     * Add TitreTerritoire entity to collection (one to many).
     */
    public function addTitreTerritoire(TitreTerritoire $titreTerritoire): static
    {
        $this->titreTerritoires[] = $titreTerritoire;

        return $this;
    }

    /**
     * Remove TitreTerritoire entity from collection (one to many).
     */
    public function removeTitreTerritoire(TitreTerritoire $titreTerritoire): static
    {
        $this->titreTerritoires->removeElement($titreTerritoire);

        return $this;
    }

    /**
     * Get TitreTerritoire entity collection (one to many).
     */
    public function getTitreTerritoires(): Collection
    {
        return $this->titreTerritoires;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'renomme'];
    } */
}
