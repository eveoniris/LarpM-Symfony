<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[Table(name: 'competence_family')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['base' => 'BaseCompetenceFamily', 'extended' => 'CompetenceFamily'])]
class BaseCompetenceFamily
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    #[Assert\NotBlank()]
    protected ?string $label = null;

    #[Column(type: Types::STRING, length: 450, nullable: true)]
    #[Assert\NotBlank()]
    protected ?string $description = null;

    /**
     * @var Collection<Technologie>
     */
    #[OneToMany(mappedBy: 'competenceFamily', targetEntity: 'Technologie', cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_family_id', nullable: false)]
    protected Collection $technologies;

    /**
     * @var Collection<Competence>
     */
    #[OneToMany(mappedBy: 'competenceFamily', targetEntity: 'Competence', cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'competence_family_id', nullable: false)]
    protected Collection $competences;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->technologies = new ArrayCollection();
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

    /**
     * Add Technologie entity to collection (one to many).
     */
    public function addTechnologie(Technologie $technologie): static
    {
        $this->technologies[] = $technologie;

        return $this;
    }

    /**
     * Remove Technologie entity from collection (one to many).
     */
    public function removeTechnologie(Technologie $technologie): static
    {
        $this->technologies->removeElement($technologie);

        return $this;
    }

    /**
     * Get Technologie entity collection (one to many).
     */
    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description'];
    } */
}
