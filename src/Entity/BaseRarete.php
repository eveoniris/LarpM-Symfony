<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'rarete')]
#[ORM\Index(columns: ['createur_id'], name: 'fk_billet_user1')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_billet_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRarete', 'extended' => 'Rarete'])]
abstract class BaseRarete
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(name: 'value', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $value = 0;

    #[OneToMany(mappedBy: 'rarete', targetEntity: Ressource::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'rarete_id', nullable: 'false')]
    protected Collection $ressources;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
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
     * Set the value of value.
     */
    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of value.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Add Ressource entity to collection (one to many).
     */
    public function addRessource(Ressource $ressource): static
    {
        $this->ressources[] = $ressource;

        return $this;
    }

    /**
     * Remove Ressource entity from collection (one to many).
     */
    public function removeRessource(Ressource $ressource): static
    {
        $this->ressources->removeElement($ressource);

        return $this;
    }

    /**
     * Get Ressource entity collection (one to many).
     */
    public function getRessources(): Collection
    {
        return $this->ressources;
    }

    public function __sleep()
    {
        return ['id', 'label', 'value'];
    }
}
