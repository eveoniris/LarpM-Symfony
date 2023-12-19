<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'groupe_has_ressource')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_has_ressource_groupe1_idx')]
#[ORM\Index(columns: ['ressource_id'], name: 'fk_groupe_has_ressource_ressource1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeHasRessource', 'extended' => 'GroupeHasRessource'])]
abstract class BaseGroupeHasRessource
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $quantite;

    #[ORM\ManyToOne(targetEntity: Groupe::class, cascade: ['persist', 'remove'], inversedBy: 'groupeHasRessources')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id')]
    protected Groupe $groupe;

    #[ORM\ManyToOne(targetEntity: Ressource::class, inversedBy: 'groupeHasRessources')]
    #[ORM\JoinColumn(name: 'ressource_id', referencedColumnName: 'id')]
    protected Ressource $ressource;

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
     * Set the value of quantite.
     */
    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Get the value of quantite.
     */
    public function getQuantite(): int
    {
        return $this->quantite;
    }

    /**
     * Set Groupe entity (many to one).
     */
    public function setGroupe(Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): Groupe
    {
        return $this->groupe;
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
    public function getRessource(): Ressource
    {
        return $this->ressource;
    }

    /* public function __sleep()
    {
        return ['id', 'quantite', 'groupe_id', 'ressource_id'];
    } */
}
