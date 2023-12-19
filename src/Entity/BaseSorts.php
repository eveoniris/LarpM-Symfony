<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'sorts')]
#[ORM\Index(columns: ['domaine_id'], name: 'fk_sorts_domaine1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseSorts', 'extended' => 'Sorts'])]
abstract class BaseSorts
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $niveau = 0;

    #[ManyToOne(targetEntity: Domaine::class, inversedBy: 'sorts')]
    #[JoinColumn(name: 'domaine_id', referencedColumnName: 'id', nullable: 'false')]
    protected Domaine $domaine;

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
     */
    public function setNiveau(int $niveau): static
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

    /* public function __sleep()
    {
        return ['id', 'label', 'description', 'domaine_id', 'documentUrl', 'niveau'];
    } */
}
