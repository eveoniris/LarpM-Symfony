<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_ressource')]
#[ORM\Index(columns: ['ressource_id'], name: 'fk_personnage_ressource_ressource1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageRessource', 'extended' => 'PersonnageRessource'])]
abstract class BasePersonnageRessource
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $nombre;

    #[ManyToOne(targetEntity: Personnage::class, cascade: ['persist', 'remove'], inversedBy: 'personnageRessources')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Personnage $personnage;

    #[ManyToOne(targetEntity: Ressource::class, inversedBy: 'personnageRessources')]
    #[JoinColumn(name: 'ressource_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Ressource $ressource;

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
    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    /**
     * Set Ressource entity (many to one).
     */
    public function setRessource(Ressource $ressource = null): static
    {
        $this->ressource = $ressource;

        return $this;
    }

    /**
     * Get Ressource entity (many to one).
     */
    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'ressource_id', 'nombre'];
    } */
}
