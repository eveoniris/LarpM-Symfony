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
#[ORM\Table(name: 'sphere')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseSphere', 'extended' => 'Sphere'])]
abstract class BaseSphere
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true, length: 45)]
    protected ?string $label = null;

    #[OneToMany(mappedBy: 'sphere', targetEntity: Priere::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'sphere_id', nullable: 'false')]
    protected Collection $prieres;

    #[ORM\ManyToMany(targetEntity: Religion::class, inversedBy: 'spheres')]
    #[ORM\JoinTable(name: 'religions_spheres')]
    #[ORM\JoinColumn(name: 'sphere_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'religion_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OrderBy(['label' => 'ASC'])]
    protected Collection $religions;

    public function __construct()
    {
        $this->prieres = new ArrayCollection();
        $this->religions = new ArrayCollection();
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
     * Add Priere entity to collection (one to many).
     */
    public function addPriere(Priere $priere): static
    {
        $this->prieres[] = $priere;

        return $this;
    }

    /**
     * Remove Priere entity from collection (one to many).
     */
    public function removePriere(Priere $priere): static
    {
        $this->prieres->removeElement($priere);

        return $this;
    }

    /**
     * Get Priere entity collection (one to many).
     */
    public function getPrieres(): Prieres
    {
        return $this->prieres;
    }

    /**
     * Add Religion entity to collection.
     */
    public function addReligion(Religion $religion): static
    {
        $religion->addSphere($this);
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Remove Religion entity from collection.
     */
    public function removeReligion(Religion $religion): static
    {
        $religion->removeSphere($this);
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Get Religion entity collection.
     */
    public function getReligions(): Collection
    {
        return $this->religions;
    }

    public function __sleep()
    {
        return ['id', 'label'];
    }
}
