<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'titre_territoire')]
#[ORM\Index(columns: ['titre_id'], name: 'fk_titre_territoire_titre1_idx')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_titre_territoire_territoire1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTitreTerritoire', 'extended' => 'TitreTerritoire'])]
abstract class BaseTitreTerritoire
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[ManyToOne(targetEntity: Titre::class, inversedBy: 'titreTerritoires')]
    #[JoinColumn(name: 'titre_id', referencedColumnName: 'id', nullable: 'false')]
    protected Titre $titre;

    #[ManyToOne(targetEntity: Territoire::class, inversedBy: 'titreTerritoires')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: 'false')]
    protected Territoire $territoire;

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
     * Set Titre entity (many to one).
     */
    public function setTitre(Titre $titre = null): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get Titre entity (many to one).
     */
    public function getTitre(): Titre
    {
        return $this->titre;
    }

    /**
     * Set Territoire entity (many to one).
     */
    public function setTerritoire(Territoire $territoire = null): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (many to one).
     */
    public function getTerritoire(): Territoire
    {
        return $this->territoire;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'titre_id', 'territoire_id'];
    } */
}
