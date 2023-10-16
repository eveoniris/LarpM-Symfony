<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
class BaseAge
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 450, nullable: true)]
    protected string $description = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $bonus = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $enableCreation = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $minimumValue = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Personnage>|\App\Entity\Personnage[]
     */
    #[OneToMany(mappedBy: 'age', targetEntity: Personnage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'age_id', nullable: 'false')]
    protected ArrayCollection $personnages;

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
        return $this->label;
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

    public function getPersonnages(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->personnages;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'bonus', 'enableCreation', 'minimumValue'];
    }
}
