<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'appelation')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseAppelation', 'extended' => 'Appelation'])]
class BaseAppelation
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(name: 'description', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description;

    #[Column(name: 'titre', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $titre = '';

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Appelation>|\App\Entity\Appelation[]
     */
    #[OneToMany(mappedBy: 'appelation', targetEntity: Appelation::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'appelation_id', nullable: 'false')]
    protected Collection $appelations;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Territoire>|\App\Entity\Territoire[]
     */
    #[OneToMany(mappedBy: 'appelation', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'appelation_id', nullable: 'false')]
    protected Collection $territoires;

    #[ManyToOne(targetEntity: Appelation::class, inversedBy: 'appelations')]
    #[JoinColumn(name: 'appelation_id', referencedColumnName: 'id')]
    protected ?Appelation $appelation;

    public function __construct()
    {
        $this->appelations = new ArrayCollection();
        $this->territoires = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function addAppelation(Appelation $appelation): self
    {
        $this->appelations[] = $appelation;

        return $this;
    }

    public function removeAppelation(Appelation $appelation): self
    {
        $this->appelations->removeElement($appelation);

        return $this;
    }

    public function getAppelations(): Collection
    {
        return $this->appelations;
    }

    public function addTerritoire(Territoire $territoire): self
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    public function removeTerritoire(Territoire $territoire): self
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    public function setAppelation(Appelation $appelation = null): self
    {
        $this->appelation = $appelation;

        return $this;
    }

    public function getAppelation(): ?self
    {
        return $this->appelation;
    }

    /* public function __sleep()
    {
        return ['id', 'appelation_id', 'label', 'description', 'titre'];
    } */
}
