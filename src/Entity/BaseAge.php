<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'age')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseAge', 'extended' => 'Age'])]
abstract class BaseAge
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 100)]
    protected string $label = '';

    #[Column(type: Types::STRING, length: 450, nullable: true)]
    protected string $description = '';

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $bonus = null;

    #[Column(name: 'enableCreation', type: Types::BOOLEAN)]
    protected bool $enableCreation = false;

    #[Column(name: 'minimumValue', type: Types::INTEGER, options: ['default' => 0])]
    protected int $minimumValue = 0;

    /**
     * @var Collection<int, Personnage>|Personnage[]
     */
    #[OneToMany(mappedBy: 'age', targetEntity: Personnage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'age_id', nullable: 'false')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setBonus(bool $bonus): self
    {
        $this->bonus = $bonus;

        return $this;
    }

    public function getBonus(): int
    {
        return $this->bonus;
    }

    public function setEnableCreation(bool $enableCreation): self
    {
        $this->enableCreation = $enableCreation;

        return $this;
    }

    public function getEnableCreation(): bool
    {
        return $this->enableCreation;
    }

    public function setMinimumValue(int $minimumValue): self
    {
        $this->minimumValue = $minimumValue;

        return $this;
    }

    public function getMinimumValue(): int
    {
        return $this->minimumValue;
    }

    public function addPersonnage(Personnage $personnage): self
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function removePersonnage(Personnage $personnage): self
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }
}
