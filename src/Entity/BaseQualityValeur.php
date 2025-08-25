<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'quality_valeur')]
#[ORM\Index(columns: ['quality_id'], name: 'fk_quality_valeur_qualite1_idx')]
#[ORM\Index(columns: ['monnaie_id'], name: 'fk_quality_valeur_monnaie1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseQualityValeur', 'extended' => 'QualityValeur'])]
abstract class BaseQualityValeur
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $nombre;

    #[ManyToOne(targetEntity: Quality::class, cascade: ['persist', 'remove'], inversedBy: 'qualityValeurs')]
    #[JoinColumn(name: 'quality_id', referencedColumnName: 'id', nullable: false)]
    protected Quality $quality;

    #[ManyToOne(targetEntity: Monnaie::class, inversedBy: 'qualityValeurs')]
    #[JoinColumn(name: 'monnaie_id', referencedColumnName: 'id', nullable: false)]
    protected Monnaie $monnaie;

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
     * Set the value of nombre.
     */
    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     */
    public function getNombre(): int
    {
        return $this->nombre;
    }

    /**
     * Set Quality entity (many to one).
     */
    public function setQuality(?Quality $quality = null): static
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * Get Quality entity (many to one).
     */
    public function getQuality(): Quality
    {
        return $this->quality;
    }

    /**
     * Set Monnaie entity (many to one).
     */
    public function setMonnaie(?Monnaie $monnaie = null): static
    {
        $this->monnaie = $monnaie;

        return $this;
    }

    /**
     * Get Monnaie entity (many to one).
     */
    public function getMonnaie(): Monnaie
    {
        return $this->monnaie;
    }

    /* public function __sleep()
    {
        return ['id', 'quality_id', 'monnaie_id', 'nombre'];
    } */
}
