<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'technologie')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTechnologie', 'extended' => 'Technologie'])]
abstract class BaseTechnologie
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, )]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 45)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected ?string $label = null;

    #[ORM\Column(type: Types::TEXT, length: 450)]
    protected ?string $description = null;

    #[ORM\Column(name: 'documentUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected bool $secret;

    /**
     * @var Collection<BaseTechnologiesRessources>
     */
    #[ORM\OneToMany(mappedBy: 'technologie', targetEntity: 'BaseTechnologiesRessources', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'technology_id', nullable: false)]
    protected Collection $technologieRessource;

    #[ORM\ManyToOne(targetEntity: 'CompetenceFamily', cascade: ['persist'], inversedBy: 'technologies')]
    #[ORM\JoinColumn(name: 'competence_family_id', referencedColumnName: 'id', nullable: true)]
    protected CompetenceFamily $competenceFamily;

    /**
     * @var Collection<Personnage>
     */
    #[ORM\ManyToMany(targetEntity: 'Personnage', mappedBy: 'technologies')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
        $this->technologieRessource = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @return Technologie
     */
    public function setId(?int $id): static
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
     *
     * @return Technologie
     */
    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string|null
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Set the value of description.
     *
     * @return Technologie
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string|null
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Get the value of documentUrl.
     */
    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    /**
     * Set the value of documentUrl.
     */
    public function setDocumentUrl(?string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of secret.
     */
    public function isSecret(): bool
    {
        return $this->secret;
    }

    /**
     * Set the value of secret.
     */
    public function setSecret(bool $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * Add BaseTechnologiesRessources entity to collection (one to many).
     *
     * @param BaseTechnologiesRessources $ressource
     *
     * @return Technologie
     */
    public function addRessources(TechnologiesRessources $ressource): static
    {
        $this->technologieRessource[] = $ressource;

        return $this;
    }

    /**
     * Remove BaseTechnologiesRessources entity from collection (one to many).
     *
     * @param BaseTechnologiesRessources $ressource
     *
     * @return Technologie
     */
    public function removeTechnologie(TechnologiesRessources $ressource): static
    {
        $this->technologieRessource->removeElement($ressource);

        return $this;
    }

    /**
     * Get BaseTechnologiesRessources entity collection (one to many).
     */
    public function getRessources(): Collection
    {
        return $this->technologieRessource;
    }

    /**
     * Set CompetenceFamily entity (many to one).
     *
     * @param CompetenceFamily|null $competenceFamily
     *
     * @return BaseTechnologie
     */
    public function setCompetenceFamily(CompetenceFamily $competenceFamily): static
    {
        $this->competenceFamily = $competenceFamily;

        return $this;
    }

    /**
     * Get CompetenceFamily entity (many to one).
     */
    public function getCompetenceFamily(): CompetenceFamily
    {
        return $this->competenceFamily;
    }

    /**
     * Add Personnage entity to collection.
     *
     * @return Technologie
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     *
     * @return Technologie
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Get Personnage entity collection.
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'description'];
    } */
}
