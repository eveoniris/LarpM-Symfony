<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    protected string $label = '';

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 450, nullable: true)]
    protected ?string $description = '';

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $diffusion = 0;

    #[OneToMany(mappedBy: 'langue', targetEntity: PersonnageLangues::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'langue_id', nullable: 'false')]
    #[OrderBy(['secret' => 'ASC', 'diffusion' => 'DESC', 'label' => 'ASC'])]
    protected ArrayCollection $personnageLangues;

    #[OneToMany(mappedBy: 'langue', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'langue_id', nullable: 'false')]
    protected ArrayCollection $territoires;

    #[OneToMany(mappedBy: 'langue', targetEntity: GroupeLangue::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_langue_id', nullable: 'false')]
    protected GroupeLangue $groupeLangue;

    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'langues')]
    protected ArrayCollection $documents;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected bool $secret = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    public function __construct()
    {
        $this->personnageLangues = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->documents = new ArrayCollection();
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
    public function getPersonnageLangues(): ArrayCollection
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
    public function getTerritoires(): ArrayCollection
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
    public function getDocuments(): ArrayCollection
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

    public function __sleep()
    {
        return ['id', 'label', 'description', 'diffusion', 'groupe_langue_id', 'secret'];
    }
}
