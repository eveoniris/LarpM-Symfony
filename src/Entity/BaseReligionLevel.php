<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'religion_level')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseReligionLevel', 'extended' => 'ReligionLevel'])]

abstract class BaseReligionLevel
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, name: "`index`")]
    protected int $index = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[OneToMany(mappedBy: 'religionLevel', targetEntity: PersonnagesReligions::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'religion_level_id', nullable: 'false')]
    protected $personnagesReligions;

    #[OneToMany(mappedBy: 'religionLevel', targetEntity: ReligionDescription::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'religion_level_id', nullable: 'false')]
    protected $religionDescriptions;

    public function __construct()
    {
        $this->personnagesReligions = new ArrayCollection();
    }

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
     * Set the value of label.
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of index.
     */
    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get the value of index.
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Add PersonnagesReligions entity to collection (one to many).
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     */
    public function getPersonnagesReligions(): Collection
    {
        return $this->personnagesReligions;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'index', 'description'];
    } */
}
