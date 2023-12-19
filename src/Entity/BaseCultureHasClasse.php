<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'culture_has_classe')]
#[ORM\Index(columns: ['culture_id'], name: 'fk_culture_has_classe_culture1_idx')]
#[ORM\Index(columns: ['classe_id'], name: 'fk_culture_has_classe_classe1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseCultureHasClasse', 'extended' => 'CultureHasClasse'])]
abstract class BaseCultureHasClasse
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Culture::class, inversedBy: 'cultureHasClasses')]
    #[ORM\JoinColumn(name: 'culture_id', referencedColumnName: 'id', nullable: 'false')]
    protected Culture $culture;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'cultureHasClasses')]
    #[ORM\JoinColumn(name: 'classe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Classe $classe;

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCulture(Culture $culture = null): static
    {
        $this->culture = $culture;

        return $this;
    }

    public function getCulture(): Culture
    {
        return $this->culture;
    }

    public function setClasse(Classe $classe = null): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getClasse(): Classe
    {
        return $this->classe;
    }

    /* public function __sleep()
    {
        return ['id', 'culture_id', 'classe_id'];
    } */
}
