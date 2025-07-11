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
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

#[ORM\Entity]
#[ORM\Table(name: 'langue')]
#[ORM\Index(columns: ['groupe_langue_id'], name: 'groupe_langue_id_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseLangue', 'extended' => 'Langue'])]
abstract class BaseLangue
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: Types::STRING, length: 100)]
    protected string $label = '';

    #[Column(type: Types::STRING, length: 450, nullable: true)]
    protected ?string $description = '';

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $diffusion = 0;

    #[OneToMany(mappedBy: 'langue', targetEntity: PersonnageLangues::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'langue_id', nullable: false)]
    #[OrderBy(['langue' => 'ASC'])]
    protected Collection $personnageLangues;

    #[OneToMany(mappedBy: 'langue', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'langue_id', nullable: false)]
    protected Collection $territoires;

    #[ORM\ManyToOne(targetEntity: GroupeLangue::class, inversedBy: 'langues')]
    #[JoinColumn(name: 'groupe_langue_id', referencedColumnName: 'id', nullable: false)]
    protected GroupeLangue $groupeLangue;

    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'langues')]
    protected Collection $documents;

    #[Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected bool $secret = false;

    #[ORM\Column(name: 'documentUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'langues')]
    protected Collection $territoireSecondaires;

    public function __construct()
    {
        $this->personnageLangues = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->territoireSecondaires = new ArrayCollection();
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
    public function getId(): ?int
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
     * Set the value of diffusion.
     *
     * @param int $diffusion
     */
    public function setDiffusion(string $diffusion): static
    {
        $this->diffusion = $diffusion;

        return $this;
    }

    /**
     * Get the value of diffusion.
     */
    public function getDiffusion(): int
    {
        return $this->diffusion ?? '';
    }

    /**
     * Add PersonnageLangues entity to collection (one to many).
     */
    public function addPersonnageLangues(PersonnageLangues $personnageLangues): static
    {
        $this->personnageLangues[] = $personnageLangues;

        return $this;
    }

    /**
     * Remove PersonnageLangues entity from collection (one to many).
     */
    public function removePersonnageLangues(PersonnageLangues $personnageLangues): static
    {
        $this->personnageLangues->removeElement($personnageLangues);

        return $this;
    }

    /**
     * Get PersonnageLangues entity collection (one to many).
     *
     * @OrderBy({"secret" = "ASC", "diffusion" = "DESC", "label" = "ASC"})
     */
    public function getPersonnageLangues(): Collection
    {
        return $this->personnageLangues;
    }

    /**
     * Add Territoire entity to collection (one to many).
     */
    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     */
    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     */
    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    /**
     * Set GroupeLangue entity (many to one).
     */
    public function setGroupeLangue(GroupeLangue $groupeLangue = null): static
    {
        $this->groupeLangue = $groupeLangue;

        return $this;
    }

    /**
     * Get GroupeLangue entity (many to one).
     */
    public function getGroupeLangue(): GroupeLangue
    {
        return $this->groupeLangue;
    }

    /**
     * Add Document entity to collection.
     */
    public function addDocument(Document $document): static
    {
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove Document entity from collection.
     */
    public function removeDocument(Document $document): static
    {
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Get Document entity collection.
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Set the value of secret.
     */
    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     */
    public function getSecret(): bool
    {
        return $this->secret;
    }

    /**
     * Set the value of documentUrl.
     */
    public function setDocumentUrl(string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of documentUrl.
     */
    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    public function getTerritoireSecondaires(): Collection
    {
        return $this->territoireSecondaires;
    }

    public function addTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires[] = $territoire;

        return $this;
    }

    public function removeTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires->removeElement($territoire);

        return $this;
    }


    /* public function __sleep()
    {
        return ['id', 'label', 'description', 'diffusion', 'groupe_langue_id', 'secret'];
    } */
}
