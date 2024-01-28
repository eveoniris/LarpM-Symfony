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
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'rangement')]
#[ORM\Index(columns: ['localisation_id'], name: 'fk_rangement_localisation1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRangement', 'extended' => 'Rangement'])]
abstract class BaseRangement
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $label = null;

    #[Column(name: '`precision`', type: Types::STRING, length: 450, nullable: true)]
    protected ?string $precision = null;

    #[OneToMany(mappedBy: 'rangement', targetEntity: Objet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'rangement_id', nullable: 'false')]
    protected Collection $objets;

    #[ManyToOne(targetEntity: Localisation::class, inversedBy: 'rangements')]
    #[JoinColumn(name: 'localisation_id', referencedColumnName: 'id', nullable: 'false')]
    protected Localisation $localisation;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
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
     * Set the value of precision.
     */
    public function setPrecision(string $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Get the value of precision.
     */
    public function getPrecision(): ?string
    {
        return $this->precision ?? '';
    }

    /**
     * Add Objet entity to collection (one to many).
     */
    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     */
    public function getObjets(): Collection
    {
        return $this->objets;
    }

    /**
     * Set Localisation entity (many to one).
     */
    public function setLocalisation(Localisation $localisation = null): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    /**
     * Get Localisation entity (many to one).
     */
    public function getLocalisation(): Localisation
    {
        return $this->localisation;
    }

    /* public function __sleep()
    {
        return ['id', 'localisation_id', 'label', 'precision'];
    } */
}
