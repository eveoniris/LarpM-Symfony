<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\BaseTechnologiesRessources.
 */
#[Table(name: 'technologies_ressources')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['base' => 'BaseTechnologiesRessources', 'extended' => 'TechnologiesRessources'])]
class BaseTechnologiesRessources
{
    #[Id]
    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true])]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[Assert\GreaterThan(0)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    protected ?int $quantite = null;

    #[ManyToOne(targetEntity: 'Technologie', inversedBy: 'technologieRessource', cascade: ['persist'])]
    #[JoinColumn(name: 'technologie_id', referencedColumnName: 'id')]
    protected \App\Entity\Technologie $technologie;

    #[ManyToOne(targetEntity: 'Ressource', inversedBy: 'technologieRessource', cascade: ['persist'])]
    #[JoinColumn(name: 'ressource_id', referencedColumnName: 'id')]
    protected $ressource;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): void
    {
        $this->quantite = $quantite;
    }

    public function getTechnologie(): Technologie
    {
        return $this->technologie;
    }

    public function setTechnologie(Technologie $technologie): void
    {
        $this->technologie = $technologie;
    }

    /**
     * @return Ressource
     */
    public function getRessource()
    {
        return $this->ressource;
    }

    public function setRessource(Ressource $ressource): void
    {
        $this->ressource = $ressource;
    }
}
