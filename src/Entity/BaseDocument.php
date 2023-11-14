<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[ORM\Table(name: 'document')]
#[ORM\Index(columns: ['user_id'], name: 'fk_document_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseDocument', 'extended' => 'Document'])]
abstract class BaseDocument
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $code = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $titre = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $documentUrl = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $cryptage = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $statut = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $auteur = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $impression;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'documents')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Langue::class, inversedBy: 'documents')]
    #[ORM\JoinTable(name: 'document_has_langue')]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'langue_id', referencedColumnName: 'id', nullable: false)]
    protected ArrayCollection $langues;

    #[ORM\ManyToMany(targetEntity: Groupe::class, inversedBy: 'documents')]
    protected ArrayCollection $groupes;

    #[ORM\ManyToMany(targetEntity: Lieu::class, inversedBy: 'documents')]
    protected ArrayCollection $lieus;

    #[ORM\ManyToMany(targetEntity: Personnage::class, inversedBy: 'documents')]
    protected ArrayCollection $personnages;

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

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code ?? '';
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre ?? '';
    }

    public function setDescription(string $description): static
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

    public function setStatut(string $statut): static
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

    public function getAuteur(): string
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

    public function setUser(User $User = null): static
    {
        $this->user = $User;

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

    public function getLangues(): ArrayCollection
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

    public function getGroupes(): ArrayCollection
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

    public function getLieus(): ArrayCollection
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

    public function getPersonnages(): ArrayCollection
    {
        return $this->personnages;
    }

    public function __sleep()
    {
        return ['id', 'code', 'titre', 'description', 'documentUrl', 'cryptage', 'statut', 'auteur', 'User_id', 'creation_date', 'update_date', 'impression'];
    }
}
