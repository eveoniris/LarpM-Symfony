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
#[ORM\Table(name: 'lieu')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseLieu', 'extended' => 'Lieu'])]
abstract class BaseLieu
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $nom;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = '';

    #[OneToMany(mappedBy: 'lieu', targetEntity: IntrigueHasLieu::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'lieu_id', nullable: 'false')]
    protected Collection $intrigueHasLieus;

    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'lieus')]
    #[ORM\JoinTable(name: 'lieu_has_document')]
    #[ORM\JoinColumn(name: 'lieu_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $documents;

    public function __construct()
    {
        $this->intrigueHasLieus = new ArrayCollection();
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
     * Set the value of nom.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     */
    public function getNom(): string
    {
        return $this->nom;
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
        return $this->description;
    }

    /**
     * Add IntrigueHasLieu entity to collection (one to many).
     */
    public function addIntrigueHasLieu(IntrigueHasLieu $intrigueHasLieu): static
    {
        $this->intrigueHasLieus[] = $intrigueHasLieu;

        return $this;
    }

    /**
     * Remove IntrigueHasLieu entity from collection (one to many).
     */
    public function removeIntrigueHasLieu(IntrigueHasLieu $intrigueHasLieu): static
    {
        $this->intrigueHasLieus->removeElement($intrigueHasLieu);

        return $this;
    }

    /**
     * Get IntrigueHasLieu entity collection (one to many).
     */
    public function getIntrigueHasLieus(): Collection
    {
        return $this->intrigueHasLieus;
    }

    /**
     * Add Document entity to collection.
     */
    public function addDocument(Document $document): static
    {
        $document->addLieu($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove Document entity from collection.
     */
    public function removeDocument(Document $document): static
    {
        $document->removeLieu($this);
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

    /* public function __sleep()
    {
        return ['id', 'nom', 'description'];
    } */
}
