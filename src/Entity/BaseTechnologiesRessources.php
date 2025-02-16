<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(name: 'technologies_ressources')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['base' => 'BaseTechnologiesRessources', 'extended' => 'TechnologiesRessources'])]
class BaseTechnologiesRessources
{
    #[Id]
    #[Column(type: Types::INTEGER, )]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::INTEGER)]
    #[Assert\GreaterThan(0)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    protected ?int $quantite = null;

    #[ManyToOne(targetEntity: 'Technologie', cascade: ['persist'], inversedBy: 'technologieRessource')]
    #[JoinColumn(name: 'technologie_id', referencedColumnName: 'id')]
    protected Technologie $technologie;

    #[ManyToOne(targetEntity: 'Ressource', cascade: ['persist'], inversedBy: 'technologiesRessources')]
    #[JoinColumn(name: 'ressource_id', referencedColumnName: 'id')]
    protected Ressource $ressource;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getTechnologie(): Technologie
    {
        return $this->technologie;
    }

    public function setTechnologie(Technologie $technologie): self
    {
        $this->technologie = $technologie;

        return $this;
    }

    public function getRessource(): Ressource
    {
        return $this->ressource;
    }

    public function setRessource(Ressource $ressource): self
    {
        $this->ressource = $ressource;

        return $this;
    }
}
