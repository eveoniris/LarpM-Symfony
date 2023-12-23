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
#[ORM\Table(name: 'level')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseLevel', 'extended' => 'Level'])]
abstract class BaseLevel
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $index;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected $label;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $cout = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $cout_favori = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $cout_meconu = 0;

    #[OneToMany(mappedBy: 'level', targetEntity: Competence::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'level_id', nullable: 'false')]
    protected ?Collection $competences;

    public function __construct()
    {
        $this->competences = new Collection();
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
     * Set the value of cout.
     */
    public function setCout(int $cout): static
    {
        $this->cout = $cout;

        return $this;
    }

    /**
     * Get the value of cout.
     */
    public function getCout(): int
    {
        return $this->cout;
    }

    /**
     * Set the value of cout_favori.
     */
    public function setCoutFavori(int $cout_favori): static
    {
        $this->cout_favori = $cout_favori;

        return $this;
    }

    /**
     * Get the value of cout_favori.
     */
    public function getCoutFavori(): int
    {
        return $this->cout_favori;
    }

    /**
     * Set the value of cout_meconu.
     */
    public function setCoutMeconu(int $cout_meconu): static
    {
        $this->cout_meconu = $cout_meconu;

        return $this;
    }

    /**
     * Get the value of cout_meconu.
     */
    public function getCoutMeconu(): int
    {
        return $this->cout_meconu;
    }

    /**
     * Add Competence entity to collection (one to many).
     */
    public function addCompetence(Competence $competence): static
    {
        $this->competences[] = $competence;

        return $this;
    }

    /**
     * Remove Competence entity from collection (one to many).
     */
    public function removeCompetence(Competence $competence): static
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    /**
     * Get Competence entity collection (one to many).
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    /* public function __sleep()
    {
        return ['id', 'index', 'label', 'cout', 'cout_favori', 'cout_meconu'];
    } */
}
