<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'culture')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseCulture', 'extended' => 'Culture'])]
abstract class BaseCulture
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $description;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description_complete = null;

    #[OneToMany(mappedBy: 'culture', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'culture_id', nullable: 'false')]
    protected Collection $territoires;

    public function __construct()
    {
        $this->territoires = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescriptionComplete(string $description_complete): static
    {
        $this->description_complete = $description_complete;

        return $this;
    }

    public function getDescriptionComplete(): string
    {
        return $this->description_complete ?? '';
    }

    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'description_complete'];
    }
}
