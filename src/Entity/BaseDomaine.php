<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

#[Entity]
#[ORM\Table(name: 'domaine')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseDomaine', 'extended' => 'Domaine'])]
abstract class BaseDomaine
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description;

    #[OneToMany(mappedBy: 'domaine', targetEntity: Sort::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'domaine_id', nullable: 'false')]
    #[OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $sorts;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'domaines')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->sorts = new ArrayCollection();
        $this->personnages = new ArrayCollection();
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

    public function addSort(Sort $sort): static
    {
        $this->sorts[] = $sort;

        return $this;
    }

    public function removeSort(Sort $sort): static
    {
        $this->sorts->removeElement($sort);

        return $this;
    }

    public function getSorts(): Collection
    {
        return $this->sorts;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description'];
    } */
}
