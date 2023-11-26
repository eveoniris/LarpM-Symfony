<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'sort')]
#[ORM\Index(columns: ['domaine_id'], name: 'fk_sort_domaine1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseSort', 'extended' => 'Sort'])]
abstract class BaseSort
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(length: 45, type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $label = '';

    #[Column(nullable: true, type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $description = null;

    #[Column(length: 45, type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $documentUrl = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $niveau = 0;

    #[ManyToOne(targetEntity: Domaine::class, inversedBy: 'sorts')]
    #[JoinColumn(name: 'domaine_id', referencedColumnName: 'id', nullable: 'false')]
    protected Domaine $domaine;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    protected bool $secret = false;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'sorts')]
    protected Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
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

    /**
     * Set the value of niveau.
     *
     * @param int $niveau
     */
    public function setNiveau($niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * Get the value of niveau.
     */
    public function getNiveau(): int
    {
        return $this->niveau;
    }

    /**
     * Set Domaine entity (many to one).
     */
    public function setDomaine(Domaine $domaine = null): static
    {
        $this->domaine = $domaine;

        return $this;
    }

    /**
     * Get Domaine entity (many to one).
     */
    public function getDomaine(): Domaine
    {
        return $this->domaine;
    }

    /**
     * Add Personnage entity to collection.
     */
    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
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

    public function __sleep()
    {
        return ['id', 'label', 'description', 'domaine_id', 'documentUrl', 'niveau'];
    }
}
