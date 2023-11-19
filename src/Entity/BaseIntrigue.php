<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'intrigue')]
#[ORM\Index(columns: ['user_id'], name: 'fk_intrigue_User1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigue', 'extended' => 'Intrigue'])]
abstract class BaseIntrigue
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $description = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $titre = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected string $text = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    protected ?string $resolution = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    protected \DateTime $date_creation;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    protected \DateTime $date_update;

    /**
     * @Column(name="`state`", type="string", length=45, nullable=true)
     */
    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $state = null;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasEvenement::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasEvenements;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasGroupe::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasGroupes;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasGroupeSecondaire::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasGroupeSecondaires;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasLieu::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasLieus;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasDocument::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasDocuments;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasModification::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasModifications;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: IntrigueHasObjectif::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $intrigueHasObjectifs;

    #[OneToMany(mappedBy: 'intrigue', targetEntity: Relecture::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'intrigue_id', nullable: 'false')]
    protected ArrayCollection $relectures;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'intrigues')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    public function __construct()
    {
        $this->intrigueHasEvenements = new ArrayCollection();
        $this->intrigueHasGroupes = new ArrayCollection();
        $this->intrigueHasGroupeSecondaires = new ArrayCollection();
        $this->intrigueHasLieus = new ArrayCollection();
        $this->intrigueHasDocuments = new ArrayCollection();
        $this->intrigueHasModifications = new ArrayCollection();
        $this->intrigueHasObjectifs = new ArrayCollection();
        $this->relectures = new ArrayCollection();
        $this->date_creation = new \DateTime();
        $this->date_update = new \DateTime();
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
     * Set the value of titre.
     */
    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get the value of titre.
     */
    public function getTitre(): string
    {
        return $this->titre ?? '';
    }

    /**
     * Set the value of text.
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     */
    public function getText(): string
    {
        return $this->text ?? '';
    }

    /**
     * Set the value of resolution.
     */
    public function setResolution(string $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    /**
     * Get the value of resolution.
     */
    public function getResolution(): string
    {
        return $this->resolution ?? '';
    }

    /**
     * Set the value of date_creation.
     */
    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    /**
     * Get the value of date_creation.
     */
    public function getDateCreation(): \DateTime
    {
        return $this->date_creation;
    }

    /**
     * Set the value of date_update.
     */
    public function setDateUpdate(\DateTime $date_update): static
    {
        $this->date_update = $date_update;

        return $this;
    }

    /**
     * Get the value of date_update.
     */
    public function getDateUpdate(): \DateTime
    {
        return $this->date_update;
    }

    /**
     * Set the value of state.
     */
    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the value of state.
     */
    public function getState(): string
    {
        return $this->state ?? '';
    }

    /**
     * Add IntrigueHasEvenement entity to collection (one to many).
     */
    public function addIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement): static
    {
        $this->intrigueHasEvenements[] = $intrigueHasEvenement;

        return $this;
    }

    /**
     * Remove IntrigueHasEvenement entity from collection (one to many).
     */
    public function removeIntrigueHasEvenement(IntrigueHasEvenement $intrigueHasEvenement): static
    {
        $this->intrigueHasEvenements->removeElement($intrigueHasEvenement);

        return $this;
    }

    /**
     * Get IntrigueHasEvenement entity collection (one to many).
     */
    public function getIntrigueHasEvenements(): ArrayCollection
    {
        return $this->intrigueHasEvenements;
    }

    /**
     * Add IntrigueHasGroupe entity to collection (one to many).
     */
    public function addIntrigueHasGroupe(IntrigueHasGroupe $intrigueHasGroupe): static
    {
        $this->intrigueHasGroupes[] = $intrigueHasGroupe;

        return $this;
    }

    /**
     * Remove IntrigueHasGroupe entity from collection (one to many).
     */
    public function removeIntrigueHasGroupe(IntrigueHasGroupe $intrigueHasGroupe): static
    {
        $this->intrigueHasGroupes->removeElement($intrigueHasGroupe);

        return $this;
    }

    /**
     * Get IntrigueHasGroupe entity collection (one to many).
     */
    public function getIntrigueHasGroupes(): ArrayCollection
    {
        return $this->intrigueHasGroupes;
    }

    /**
     * Add IntrigueHasGroupeSecondaire entity to collection (one to many).
     */
    public function addIntrigueHasGroupeSecondaire(IntrigueHasGroupeSecondaire $intrigueHasGroupeSecondaire): static
    {
        $this->intrigueHasGroupeSecondaires[] = $intrigueHasGroupeSecondaire;

        return $this;
    }

    /**
     * Remove IntrigueHasGroupeSecondaire entity from collection (one to many).
     */
    public function removeIntrigueHasGroupeSecondaire(IntrigueHasGroupeSecondaire $intrigueHasGroupeSecondaire): static
    {
        $this->intrigueHasGroupeSecondaires->removeElement($intrigueHasGroupeSecondaire);

        return $this;
    }

    /**
     * Get IntrigueHasGroupeSecondaire entity collection (one to many).
     */
    public function getIntrigueHasGroupeSecondaires(): ArrayCollection
    {
        return $this->intrigueHasGroupeSecondaires;
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
    public function getIntrigueHasLieus(): ArrayCollection
    {
        return $this->intrigueHasLieus;
    }

    /**
     * Add IntrigueHasDocument entity to collection (one to many).
     */
    public function addIntrigueHasDocument(IntrigueHasDocument $intrigueHasDocument): static
    {
        $this->intrigueHasDocuments[] = $intrigueHasDocument;

        return $this;
    }

    /**
     * Remove IntrigueHasDocument entity from collection (one to many).
     */
    public function removeIntrigueHasDocument(IntrigueHasDocument $intrigueHasDocument): static
    {
        $this->intrigueHasDocuments->removeElement($intrigueHasDocument);

        return $this;
    }

    /**
     * Get IntrigueHasDocument entity collection (one to many).
     */
    public function getIntrigueHasDocuments(): ArrayCollection
    {
        return $this->intrigueHasDocuments;
    }

    /**
     * Add IntrigueHasModification entity to collection (one to many).
     */
    public function addIntrigueHasModification(IntrigueHasModification $intrigueHasModification): static
    {
        $this->intrigueHasModifications[] = $intrigueHasModification;

        return $this;
    }

    /**
     * Remove IntrigueHasModification entity from collection (one to many).
     */
    public function removeIntrigueHasModification(IntrigueHasModification $intrigueHasModification): static
    {
        $this->intrigueHasModifications->removeElement($intrigueHasModification);

        return $this;
    }

    /**
     * Get IntrigueHasModification entity collection (one to many).
     */
    public function getIntrigueHasModifications(): ArrayCollection
    {
        return $this->intrigueHasModifications;
    }

    /**
     * Add IntrigueHasObjectif entity to collection (one to many).
     */
    public function addIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif): static
    {
        $this->intrigueHasObjectifs[] = $intrigueHasObjectif;

        return $this;
    }

    /**
     * Remove IntrigueHasObjectif entity from collection (one to many).
     */
    public function removeIntrigueHasObjectif(IntrigueHasObjectif $intrigueHasObjectif): static
    {
        $this->intrigueHasObjectifs->removeElement($intrigueHasObjectif);

        return $this;
    }

    /**
     * Get IntrigueHasObjectif entity collection (one to many).
     */
    public function getIntrigueHasObjectifs(): ArrayCollection
    {
        return $this->intrigueHasObjectifs;
    }

    /**
     * Add Relecture entity to collection (one to many).
     */
    public function addRelecture(Relecture $relecture): static
    {
        $this->relectures[] = $relecture;

        return $this;
    }

    /**
     * Remove Relecture entity from collection (one to many).
     */
    public function removeRelecture(Relecture $relecture): static
    {
        $this->relectures->removeElement($relecture);

        return $this;
    }

    /**
     * Get Relecture entity collection (one to many).
     */
    public function getRelectures(): ArrayCollection
    {
        return $this->relectures;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(User $User = null): static
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function __sleep()
    {
        return ['id', 'description', 'titre', 'text', 'resolution', 'date_creation', 'date_update', 'User_id', 'state'];
    }
}
