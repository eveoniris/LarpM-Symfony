<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'objet_carac')]
#[ORM\Index(columns: ['objet_id'], name: 'fk_objet_carac_objet1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseObjetCarac', 'extended' => 'ObjetCarac'])]
abstract class BaseObjetCarac
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $taille = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $poid = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 6, nullable: true)]
    protected ?string $couleur = null;

    #[ORM\OneToOne(mappedBy: 'objetCaracs', targetEntity: Objet::class, cascade: ['persist', 'remove', 'merge', 'detach', 'all'])]
    #[ORM\JoinColumn(name: 'objet_id', referencedColumnName: 'id')]
    protected Objet $objet;

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
     * Set the value of taille.
     */
    public function setTaille(string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    /**
     * Get the value of taille.
     */
    public function getTaille(): string
    {
        return $this->taille ?? '';
    }

    /**
     * Set the value of poid.
     */
    public function setPoid(string $poid): static
    {
        $this->poid = $poid;

        return $this;
    }

    /**
     * Get the value of poid.
     */
    public function getPoid(): string
    {
        return $this->poid ?? '';
    }

    /**
     * Set the value of couleur.
     */
    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get the value of couleur.
     */
    public function getCouleur(): string
    {
        return $this->couleur ?? '';
    }

    /**
     * Set Objet entity (one to one).
     */
    public function setObjet(Objet $objet): static
    {
        $this->objet = $objet;

        return $this;
    }

    /**
     * Get Objet entity (one to one).
     */
    public function getObjet(): object
    {
        return $this->objet;
    }

    /* public function __sleep()
    {
        return ['id', 'objet_id', 'taille', 'poid', 'couleur'];
    } */
}
