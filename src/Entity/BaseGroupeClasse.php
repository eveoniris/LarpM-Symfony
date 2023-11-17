<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'groupe_classe')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_classe_groupe1_idx')]
#[ORM\Index(columns: ['classe_id'], name: 'fk_groupe_classe_classe1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeClasse', 'extended' => 'GroupeClasse'])]
abstract class BaseGroupeClasse
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: Groupe::class, cascade: ['persist'], inversedBy: 'groupeClasses')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Groupe $groupe;

    #[ManyToOne(targetEntity: Classe::class, cascade: ['persist'], inversedBy: 'groupeClasses')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Classe $classe;

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
     * Set Classe entity (many to one).
     */
    public function setClasse(Classe $classe = null): static
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * Get Classe entity (many to one).
     */
    public function getClasse(): Classe
    {
        return $this->classe;
    }

    public function __sleep()
    {
        return ['id', 'groupe_id', 'classe_id'];
    }
}
