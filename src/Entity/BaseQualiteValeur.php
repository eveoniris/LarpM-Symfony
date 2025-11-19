<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'qualite_valeur')]
#[ORM\Index(columns: ['qualite_id'], name: 'fk_qualite_valeur_qualite1_idx')]
#[ORM\Index(columns: ['monnaie_id'], name: 'fk_qualite_valeur_monnaie1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseQualiteValeur', 'extended' => 'QualiteValeur'])]
abstract class BaseQualiteValeur
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $monnaie_id;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $qualite_id;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $nombre;

    #[ManyToOne(targetEntity: Qualite::class, inversedBy: 'qualiteValeurs')]
    #[JoinColumn(name: 'qualite_id', referencedColumnName: 'id', nullable: false)]
    protected Qualite $qualite;

    #[ManyToOne(targetEntity: Monnaie::class, inversedBy: 'qualityValeurs')]
    #[JoinColumn(name: 'monnaie_id', referencedColumnName: 'id', nullable: false)]
    protected Monnaie $monnaie;

    /**
     * Set the value of qualite_id.
     */
    public function setQualiteId(int $qualite_id): static
    {
        $this->qualite_id = $qualite_id;

        return $this;
    }

    /**
     * Get the value of qualite_id.
     */
    public function getQualiteId(): int
    {
        return $this->qualite_id;
    }

    /**
     * Set the value of monnaie_id.
     */
    public function setMonnaieId(int $monnaie_id): QualiteValeur
    {
        $this->monnaie_id = $monnaie_id;

        return $this;
    }

    /**
     * Get the value of monnaie_id.
     */
    public function getMonnaieId(): int
    {
        return $this->monnaie_id;
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
     * Set Qualite entity (many to one).
     */
    public function setQualite(?Qualite $qualite = null): static
    {
        $this->qualite = $qualite;

        return $this;
    }

    /**
     * Get Qualite entity (many to one).
     */
    public function getQualite(): Qualite
    {
        return $this->qualite;
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
        return ['qualite_id', 'monnaie_id', 'nombre'];
    } */
}
