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
#[ORM\Table(name: 'objectif')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseObjectif', 'extended' => 'Objectif'])]
class BaseObjectif
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'text', type: 'string', length: 450)]
    protected string $text = '';

    #[Column(type: 'datetime')]
    protected \DateTime $date_creation;

    #[Column(type: 'datetime')]
    protected \DateTime $date_update;

    #[OneToMany(mappedBy: 'objectif', targetEntity: IntrigueHasObjectif::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'objectif_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasObjectifs;

    public function __construct()
    {
        $this->intrigueHasObjectifs = new ArrayCollection();
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setText($text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setDateCreation($date_creation): self
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateCreation(): \DateTime
    {
        return $this->date_creation;
    }

    public function setDateUpdate($date_update): self
    {
        $this->date_update = $date_update;

        return $this;
    }

    public function getDateUpdate(): \DateTime
    {
        return $this->date_update;
    }

    public function addIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif): self
    {
        $this->intrigueHasObjectifs[] = $intrigueHasObjectif;

        return $this;
    }

    public function removeIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif): self
    {
        $this->intrigueHasObjectifs->removeElement($intrigueHasObjectif);

        return $this;
    }

    public function getIntrigueHasObjectifs(): Collection
    {
        return $this->intrigueHasObjectifs;
    }

    public function __sleep()
    {
        return ['id', 'text', 'date_creation', 'date_update'];
    }
}