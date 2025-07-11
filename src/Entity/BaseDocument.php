<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[ORM\Table(name: 'document')]
#[ORM\Index(columns: ['user_id'], name: 'fk_document_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseDocument', 'extended' => 'Document'])]
abstract class BaseDocument
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Column(type: Types::STRING, length: 45)]
    protected ?string $code = '';

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Column(type: Types::STRING, length: 45)]
    protected ?string $titre = '';

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(name: 'documentUrl', type: Types::STRING, length: 45)]
    protected string $documentUrl = '';

    #[Column(type: Types::BOOLEAN)]
    protected bool $cryptage = false;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $statut = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $auteur = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $impression;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'documents')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Langue::class, inversedBy: 'documents')]
    #[ORM\JoinTable(name: 'document_has_langue')]
    #[JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'langue_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $langues;

    #[ORM\ManyToMany(targetEntity: Groupe::class, mappedBy: 'documents')]
    protected Collection $groupes;

    #[ORM\ManyToMany(targetEntity: Lieu::class, mappedBy: 'documents')]
    protected Collection $lieus;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'documents')]
    protected Collection $personnages;

    #[ORM\OneToMany(mappedBy: 'document', targetEntity: IntrigueHasDocument::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'document_id', nullable: false)]
    protected Collection $intrigueHasDocuments;

    public function __construct()
    {
        $this->langues = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->lieus = new ArrayCollection();
        $this->personnages = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code ?? '';
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre ?? '';
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDocumentUrl(string $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    public function getDocumentUrl(): string
    {
        return $this->documentUrl;
    }

    public function setCryptage(bool $cryptage): static
    {
        $this->cryptage = $cryptage;

        return $this;
    }

    public function getCryptage(): bool
    {
        return $this->cryptage;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut ?? '';
    }

    public function setAuteur(string $auteur)
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setCreationDate(?\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(?\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setImpression(bool $impression): static
    {
        $this->impression = $impression;

        return $this;
    }

    public function getImpression(): bool
    {
        return $this->impression;
    }

    public function setUser(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addLangue(Langue $langue): static
    {
        $langue->addDocument($this);
        $this->langues[] = $langue;

        return $this;
    }

    public function removeLangue(Langue $langue): static
    {
        $langue->removeDocument($this);
        $this->langues->removeElement($langue);

        return $this;
    }

    public function getLangues(): Collection
    {
        return $this->langues;
    }

    public function addGroupe(Groupe $groupe): static
    {
        $this->groupes[] = $groupe;

        return $this;
    }

    public function removeGroupe(Groupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addLieu(Lieu $lieu): static
    {
        $this->lieus[] = $lieu;

        return $this;
    }

    public function removeLieu(Lieu $lieu): static
    {
        $this->lieus->removeElement($lieu);

        return $this;
    }

    public function getLieus(): Collection
    {
        return $this->lieus;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    /* public function __sleep()
    {
        return ['id', 'code', 'titre', 'description', 'documentUrl', 'cryptage', 'statut', 'auteur', 'User_id', 'creation_date', 'update_date', 'impression'];
    } */
}
