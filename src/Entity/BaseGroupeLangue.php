<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'groupe_langue')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeLangue', 'extended' => 'GroupeLangue'])]
abstract class BaseGroupeLangue
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 180, unique: true)]
    protected string $label;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected $couleur;

    #[OneToMany(mappedBy: 'groupeLangue', targetEntity: Langue::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_langue_id', nullable: 'false')]
    protected Collection $langues;

    public function __construct()
    {
        $this->langues = new ArrayCollection();
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
     * Set the value of couleur.
     */
    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get the value of couleur.
     */
    public function getCouleur(): string
    {
        return $this->couleur;
    }

    /**
     * Add Langue entity to collection (one to many).
     */
    public function addLangue(Langue $langue): static
    {
        $this->langues[] = $langue;

        return $this;
    }

    /**
     * Remove Langue entity from collection (one to many).
     */
    public function removeLangue(Langue $langue): static
    {
        $this->langues->removeElement($langue);

        return $this;
    }

    /**
     * Get Langue entity collection (one to many).
     */
    public function getLangues(): Collection
    {
        return $this->langues;
    }

    public function __sleep()
    {
        return ['id', 'label', 'couleur'];
    }
}
