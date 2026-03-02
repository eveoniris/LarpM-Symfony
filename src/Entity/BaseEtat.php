<?php

declare(strict_types=1);

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
#[ORM\Table(name: 'etat')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseEtat', 'extended' => 'Etat'])]
abstract class BaseEtat
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label;

    /**
     * @var ArrayCollection<int, Objet>|Objet[]
     */
    /** @var Collection<int, Objet> */
    #[OneToMany(mappedBy: 'etat', targetEntity: Objet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'etat_id', nullable: false)]
    protected Collection $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
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

    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /** @return Collection<int, Objet> */
    public function getObjets(): Collection
    {
        return $this->objets;
    }

    /* public function __sleep()
     * {
     * return ['id', 'label'];
     * } */
}
