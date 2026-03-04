<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BaseUserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use SensitiveParameter;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: BaseUserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['etat_civil_id'], name: 'fk_user_etat_civil1_idx')]
#[ORM\Index(columns: ['personnage_secondaire_id'], name: 'fk_user_personnage_secondaire1_idx')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_user_personnage1_idx')]
#[ORM\UniqueConstraint(name: 'email_UNIQUE', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'username_UNIQUE', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'id_UNIQUE', columns: ['id'])]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseUser', 'extended' => 'User'])]
#[UniqueEntity(['email', 'username'])]
abstract class BaseUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Column(options: ['unsigned' => true])]
    protected ?int $id = null;

    #[Column(name: 'email', type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    protected string $email = '';

    #[Column(type: Types::STRING, length: 255)]
    protected ?string $password = '';

    #[Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected ?string $pwd = '';

    #[Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    // #[Assert\Required]
    protected ?string $username = null;

    /** @var array<int, string>|null */
    #[Column(type: 'json')]
    protected ?array $roles = [];

    #[Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $salt = null;

    #[Column(type: Types::STRING, length: 255)]
    protected string $rights = '';

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $creation_date = null;

    #[Column(name: 'isEnabled', type: Types::BOOLEAN)]
    protected ?bool $isEnabled = false;

    #[Column(name: 'confirmationToken', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $confirmationToken = null;

    #[Column(name: 'timePasswordResetRequested', type: Types::INTEGER, nullable: true)]
    protected ?int $timePasswordResetRequested = null;

    #[Column(name: 'trombineUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $trombineUrl = null;

    #[Column(name: 'lastConnectionDate', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $lastConnectionDate = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $coeur = 0;

    /** @var Collection<int, Background> */
    #[OneToMany(mappedBy: 'user', targetEntity: Background::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $backgrounds;

    /** @var Collection<int, Billet> */
    #[OneToMany(mappedBy: 'user', targetEntity: Billet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'createur_id', nullable: false)]
    protected Collection $billets;

    /** @var Collection<int, Debriefing> */
    #[OneToMany(mappedBy: 'user', targetEntity: Debriefing::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $debriefings;

    /** @var Collection<int, Debriefing> */
    #[OneToMany(mappedBy: 'player', targetEntity: Debriefing::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'player_id', nullable: false)]
    protected Collection $playerDebriefings;

    /** @var Collection<int, Document> */
    #[OneToMany(mappedBy: 'user', targetEntity: Document::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $documents;

    /** @var Collection<int, Groupe> */
    #[OneToMany(mappedBy: 'userRelatedByScenaristeId', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'scenariste_id', nullable: false)]
    protected Collection $groupeRelatedByScenaristeIds;

    /** @var Collection<int, Groupe> */
    #[OneToMany(mappedBy: 'userRelatedByResponsableId', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: false)]
    protected Collection $groupeRelatedByResponsableIds;

    /** @var Collection<int, Intrigue> */
    #[OneToMany(mappedBy: 'user', targetEntity: Intrigue::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $intrigues;

    /** @var Collection<int, IntrigueHasModification> */
    #[OneToMany(mappedBy: 'user', targetEntity: IntrigueHasModification::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $intrigueHasModifications;

    /** @var Collection<int, Message> */
    #[OneToMany(mappedBy: 'userRelatedByAuteur', targetEntity: Message::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'auteur', nullable: false)]
    protected Collection $messageRelatedByAuteurs;

    /** @var Collection<int, Message> */
    #[OneToMany(mappedBy: 'userRelatedByDestinataire', targetEntity: Message::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'destinataire', nullable: false)]
    #[OrderBy(['update_date' => 'DESC'])]
    protected Collection $messageRelatedByDestinataires;

    /** @var Collection<int, Notification> */
    #[OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $notifications;

    /** @var Collection<int, Objet> */
    #[OneToMany(mappedBy: 'user', targetEntity: Objet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: false)]
    protected Collection $objets;

    /** @var Collection<int, Participant> */
    #[OneToMany(mappedBy: 'user', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    #[OrderBy(['id' => 'ASC'])]
    protected Collection $participants;

    /** @var Collection<int, Personnage> */
    #[OneToMany(mappedBy: 'user', targetEntity: Personnage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $personnages;

    /** @var Collection<int, PersonnageBackground> */
    #[OneToMany(mappedBy: 'user', targetEntity: PersonnageBackground::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $personnageBackgrounds;

    /** @var Collection<int, Question> */
    #[OneToMany(mappedBy: 'user', targetEntity: Question::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $questions;

    /** @var Collection<int, Relecture> */
    #[OneToMany(mappedBy: 'user', targetEntity: Relecture::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $relectures;

    /** @var Collection<int, Restriction> */
    #[OneToMany(mappedBy: 'userRelatedByAuteurId', targetEntity: Restriction::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'auteur_id', nullable: false)]
    protected Collection $restrictionRelatedByAuteurIds;

    /** @var Collection<int, Rumeur> */
    #[OneToMany(mappedBy: 'user', targetEntity: Rumeur::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: false)]
    protected Collection $rumeurs;

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: EtatCivil::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'etat_civil_id', referencedColumnName: 'id')]
    protected ?EtatCivil $etatCivil = null;

    #[ORM\ManyToOne(targetEntity: PersonnageSecondaire::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[JoinColumn(name: 'personnage_secondaire_id', referencedColumnName: 'id')]
    protected ?PersonnageSecondaire $personnageSecondaire = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected ?Personnage $personnage = null;

    /** @var Collection<int, Restriction> */
    #[ORM\ManyToMany(targetEntity: Restriction::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_has_restriction')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'restriction_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $restrictions;

    /**
     * @var Collection<int, BaseSecondaryGroup>
     */
    #[OneToMany(mappedBy: 'scenariste', targetEntity: SecondaryGroup::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'scenariste_id', nullable: false)]
    private Collection $secondaryGroups;

    /** @var Collection<int, LogAction> */
    #[OneToMany(mappedBy: 'user', targetEntity: LogAction::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: true)]
    private Collection $logActions;

    /** @var Collection<int, QrCodeScanLog> */
    #[OneToMany(mappedBy: 'user', targetEntity: QrCodeScanLog::class)]
    private Collection $qrCodeScanLogs;

    #[Column(length: 255, nullable: true)]
    private ?string $email_contact = null;

    public function __construct()
    {
        $this->backgrounds = new ArrayCollection();
        $this->billets = new ArrayCollection();
        $this->debriefings = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->groupeRelatedByScenaristeIds = new ArrayCollection();
        $this->groupeRelatedByResponsableIds = new ArrayCollection();
        $this->secondaryGroups = new ArrayCollection();
        $this->intrigues = new ArrayCollection();
        $this->intrigueHasModifications = new ArrayCollection();
        $this->messageRelatedByAuteurs = new ArrayCollection();
        $this->messageRelatedByDestinataires = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->objets = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->personnageBackgrounds = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->relectures = new ArrayCollection();
        $this->restrictionRelatedByAuteurIds = new ArrayCollection();
        $this->rumeurs = new ArrayCollection();
        $this->restrictions = new ArrayCollection();
        $this->logActions = new ArrayCollection();
        $this->qrCodeScanLogs = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    /**
     * Returns the Username, if not empty, otherwise the email address.
     *
     * Email is returned as a fallback because Username is optional,
     * but the Symfony Security system depends on getUsername() returning a value.
     * Use getRealUsername() to get the actual Username value.
     *
     * This method is required by the UserInterface.
     *
     * @return string the Username, if not empty, otherwise the email
     *
     * @see getRealUsername
     */
    public function getUsername(): string
    {
        return $this->username ?: $this->email;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function addBackground(Background $background): static
    {
        $this->backgrounds[] = $background;

        return $this;
    }

    /**
     * Add Billet entity to collection (one to many).
     */
    public function addBillet(Billet $billet): static
    {
        $this->billets[] = $billet;

        return $this;
    }

    /**
     * Add Debriefing entity to collection (one to many).
     */
    public function addDebriefing(Debriefing $debriefing): static
    {
        $this->debriefings[] = $debriefing;

        return $this;
    }

    /**
     * Add Document entity to collection (one to many).
     */
    public function addDocument(Document $document): static
    {
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Add Groupe entity related by `responsable_id` to collection (one to many).
     */
    public function addGroupeRelatedByResponsableId(Groupe $groupe): static
    {
        $this->groupeRelatedByResponsableIds[] = $groupe;

        return $this;
    }

    /**
     * Add Groupe entity related by `scenariste_id` to collection (one to many).
     */
    public function addGroupeRelatedByScenaristeId(Groupe $groupe): static
    {
        $this->groupeRelatedByScenaristeIds[] = $groupe;

        return $this;
    }

    /**
     * Add Intrigue entity to collection (one to many).
     */
    public function addIntrigue(Intrigue $intrigue): static
    {
        $this->intrigues[] = $intrigue;

        return $this;
    }

    /**
     * Add IntrigueHasModification entity to collection (one to many).
     */
    public function addIntrigueHasModification(IntrigueHasModification $intrigueHasModification): static
    {
        $this->intrigueHasModifications[] = $intrigueHasModification;

        return $this;
    }

    public function addLogAction(LogAction $logAction): static
    {
        if (!$this->logActions->contains($logAction)) {
            $this->logActions->add($logAction);
            /* @phpstan-ignore argument.type */
            $logAction->setUser($this);
        }

        return $this;
    }

    /**
     * Add Message entity related by `auteur` to collection (one to many).
     */
    public function addMessageRelatedByAuteur(Message $message): static
    {
        $this->messageRelatedByAuteurs[] = $message;

        return $this;
    }

    /**
     * Add Message entity related by `destinataire` to collection (one to many).
     */
    public function addMessageRelatedByDestinataire(Message $message): static
    {
        $this->messageRelatedByDestinataires[] = $message;

        return $this;
    }

    /**
     * Add Notification entity to collection (one to many).
     */
    public function addNotification(Notification $notification): static
    {
        $this->notifications[] = $notification;

        return $this;
    }

    /**
     * Add Objet entity to collection (one to many).
     */
    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Add Participant entity to collection (one to many).
     */
    public function addParticipant(Participant $participant): static
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Add Personnage entity to collection (one to many).
     */
    public function addPersonnage(Personnage $personnage): static
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Add PersonnageBackground entity to collection (one to many).
     */
    public function addPersonnageBackground(PersonnageBackground $personnageBackground): static
    {
        $this->personnageBackgrounds[] = $personnageBackground;

        return $this;
    }

    /**
     * Add Question entity to collection (one to many).
     */
    public function addQuestion(Question $question): static
    {
        $this->questions->add($question);

        return $this;
    }

    /**
     * Add Relecture entity to collection (one to many).
     */
    public function addRelecture(Relecture $relecture): static
    {
        $this->relectures->add($relecture);

        return $this;
    }

    public function addRestriction(Restriction $restriction): static
    {
        /* @phpstan-ignore argument.type */
        $restriction->addUser($this);
        $this->restrictions->add($restriction);

        return $this;
    }

    /**
     * Add Restriction entity related by `auteur_id` to collection (one to many).
     */
    public function addRestrictionRelatedByAuteurId(Restriction $restriction): static
    {
        $this->restrictionRelatedByAuteurIds->add($restriction);

        return $this;
    }

    /**
     * Add Rumeur entity to collection (one to many).
     */
    public function addRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->add($rumeur);

        return $this;
    }

    public function addSecondaryGroups(BaseSecondaryGroup $secondaryGroups): static
    {
        if (!$this->secondaryGroups->contains($secondaryGroups)) {
            $this->secondaryGroups->add($secondaryGroups);
            /* @phpstan-ignore argument.type */
            $secondaryGroups->setScenariste($this);
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Get Background entity collection (one to many).
     *
     * @return Collection<int, Background>
     */
    public function getBackgrounds()
    {
        return $this->backgrounds;
    }

    /**
     * Get Billet entity collection (one to many).
     *
     * @return Collection<int, Billet>
     */
    public function getBillets()
    {
        return $this->billets;
    }

    /**
     * Get the value of coeur.
     *
     * @return int
     */
    public function getCoeur()
    {
        return $this->coeur;
    }

    public function setCoeur(?int $coeur): static
    {
        $this->coeur = $coeur;

        return $this;
    }

    /**
     * Get the value of confirmationToken.
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * Set the value of confirmationToken.
     */
    public function setConfirmationToken(#[SensitiveParameter] ?string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Set the value of creation_date.
     *
     * @param DateTime $creation_date
     */
    public function setCreationDate($creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get Debriefing entity collection (one to many).
     *
     * @return Collection<int, Debriefing>
     */
    public function getDebriefings()
    {
        return $this->debriefings;
    }

    /**
     * Get Document entity collection (one to many).
     *
     * @return Collection<int, Document>
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->email_contact;
    }

    public function setEmailContact(?string $email_contact): static
    {
        $this->email_contact = $email_contact;

        return $this;
    }

    public function getEtatCivil(): ?EtatCivil
    {
        return $this->etatCivil;
    }

    public function setEtatCivil(EtatCivil $etatCivil): static
    {
        $this->etatCivil = $etatCivil;

        return $this;
    }

    /**
     * Get Groupe entity related by `responsable_id` collection (one to many).
     *
     * @return Collection<int, Groupe>
     */
    public function getGroupeRelatedByResponsableIds()
    {
        return $this->groupeRelatedByResponsableIds;
    }

    /**
     * Get Groupe entity related by `scenariste_id` collection (one to many).
     *
     * @return Collection<int, Groupe>
     */
    public function getGroupeRelatedByScenaristeIds()
    {
        return $this->groupeRelatedByScenaristeIds;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get IntrigueHasModification entity collection (one to many).
     *
     * @return Collection<int, IntrigueHasModification>
     */
    public function getIntrigueHasModifications()
    {
        return $this->intrigueHasModifications;
    }

    /**
     * Get Intrigue entity collection (one to many).
     *
     * @return Collection<int, Intrigue>
     */
    public function getIntrigues()
    {
        return $this->intrigues;
    }

    /**
     * Get the value of isEnabled.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get the value of lastConnectionDate.
     *
     * @return DateTime
     */
    public function getLastConnectionDate()
    {
        return $this->lastConnectionDate;
    }

    /**
     * Set the value of lastConnectionDate.
     *
     * @param DateTime $lastConnectionDate
     */
    public function setLastConnectionDate($lastConnectionDate): static
    {
        $this->lastConnectionDate = $lastConnectionDate;

        return $this;
    }

    /**
     * @return Collection<int, LogAction>
     */
    public function getLogActions(): Collection
    {
        return $this->logActions;
    }

    /**
     * Get Message entity related by `auteur` collection (one to many).
     *
     * @return Collection<int, Message>
     */
    public function getMessageRelatedByAuteurs()
    {
        return $this->messageRelatedByAuteurs;
    }

    /**
     * Get Message entity related by `destinataire` collection (one to many).
     *
     * @return Collection<int, Message>
     */
    public function getMessageRelatedByDestinataires()
    {
        return $this->messageRelatedByDestinataires;
    }

    /**
     * Get Notification entity collection (one to many).
     *
     * @return Collection<int, Notification>
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * Get Objet entity collection (one to many).
     *
     * @return Collection<int, Objet>
     */
    public function getObjets()
    {
        return $this->objets;
    }

    /**
     * Get Participant entity collection (one to many).
     *
     * @return Collection<int, Participant>
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[SensitiveParameter] string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get PersonnageBackground entity collection (one to many).
     *
     * @return Collection<int, PersonnageBackground>
     */
    public function getPersonnageBackgrounds()
    {
        return $this->personnageBackgrounds;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     */
    public function getPersonnageSecondaire(): ?PersonnageSecondaire
    {
        return $this->personnageSecondaire;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     */
    public function setPersonnageSecondaire(?PersonnageSecondaire $personnageSecondaire = null): static
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get Personnage entity collection (one to many).
     *
     * @return Collection<int, Personnage>
     */
    public function getPersonnages()
    {
        return $this->personnages;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPwd(): ?string
    {
        return $this->pwd;
    }

    public function setPwd(#[SensitiveParameter] string $password): static
    {
        $this->pwd = $password;

        return $this;
    }

    /**
     * Get Question entity collection (one to many).
     *
     * @return Collection<int, Question>
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Get Relecture entity collection (one to many).
     *
     * @return Collection<int, Relecture>
     */
    public function getRelectures()
    {
        return $this->relectures;
    }

    /**
     * Get Restriction entity related by `auteur_id` collection (one to many).
     */
    /** @return Collection<int, Restriction> */
    public function getRestrictionRelatedByAuteurIds(): Collection
    {
        return $this->restrictionRelatedByAuteurIds;
    }

    /** @return Collection<int, Restriction> */
    public function getRestrictions(): Collection
    {
        return $this->restrictions;
    }

    /**
     * Get the value of rights.
     *
     * @return string
     */
    public function getRights()
    {
        return $this->rights;
    }

    public function setRights(?string $rights): static
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * @see UserInterface
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<int, string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /** @return Collection<int, Rumeur> */
    public function getRumeurs(): Collection
    {
        return $this->rumeurs;
    }

    /**
     * @deprecated
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @deprecated
     */
    public function setSalt(?string $salt): static
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @return Collection<int, BaseSecondaryGroup>
     */
    public function getSecondaryGroups(): Collection
    {
        return $this->secondaryGroups;
    }

    /**
     * Get the value of timePasswordResetRequested.
     *
     * @return int
     */
    public function getTimePasswordResetRequested()
    {
        return $this->timePasswordResetRequested;
    }

    /**
     * Set the value of timePasswordResetRequested.
     *
     * @param int $timePasswordResetRequested
     */
    public function setTimePasswordResetRequested($timePasswordResetRequested): static
    {
        $this->timePasswordResetRequested = $timePasswordResetRequested;

        return $this;
    }

    /**
     * Get the value of trombineUrl.
     *
     * @return string
     */
    public function getTrombineUrl()
    {
        return $this->trombineUrl;
    }

    /**
     * Set the value of trombineUrl.
     *
     * @param string $trombineUrl
     */
    public function setTrombineUrl($trombineUrl): static
    {
        $this->trombineUrl = $trombineUrl;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function removeBackground(Background $background): static
    {
        $this->backgrounds->removeElement($background);

        return $this;
    }

    /**
     * Remove Billet entity from collection (one to many).
     */
    public function removeBillet(Billet $billet): static
    {
        $this->billets->removeElement($billet);

        return $this;
    }

    /**
     * Remove Debriefing entity from collection (one to many).
     */
    public function removeDebriefing(Debriefing $debriefing): static
    {
        $this->debriefings->removeElement($debriefing);

        return $this;
    }

    /**
     * Remove Document entity from collection (one to many).
     */
    public function removeDocument(Document $document): static
    {
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Remove Groupe entity related by `responsable_id` from collection (one to many).
     */
    public function removeGroupeRelatedByResponsableId(Groupe $groupe): static
    {
        $this->groupeRelatedByResponsableIds->removeElement($groupe);

        return $this;
    }

    /**
     * Remove Groupe entity related by `scenariste_id` from collection (one to many).
     */
    public function removeGroupeRelatedByScenaristeId(Groupe $groupe): static
    {
        $this->groupeRelatedByScenaristeIds->removeElement($groupe);

        return $this;
    }

    /**
     * Remove Intrigue entity from collection (one to many).
     */
    public function removeIntrigue(Intrigue $intrigue): static
    {
        $this->intrigues->removeElement($intrigue);

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

    public function removeLogAction(LogAction $logAction): static
    {
        // set the owning side to null (unless already changed)
        if ($this->logActions->removeElement($logAction) && $logAction->getUser() === $this) {
            $logAction->setUser(null);
        }

        return $this;
    }

    /**
     * Remove Message entity related by `auteur` from collection (one to many).
     */
    public function removeMessageRelatedByAuteur(Message $message): static
    {
        $this->messageRelatedByAuteurs->removeElement($message);

        return $this;
    }

    /**
     * Remove Message entity related by `destinataire` from collection (one to many).
     */
    public function removeMessageRelatedByDestinataire(Message $message): static
    {
        $this->messageRelatedByDestinataires->removeElement($message);

        return $this;
    }

    /**
     * Remove Notification entity from collection (one to many).
     */
    public function removeNotification(Notification $notification): static
    {
        $this->notifications->removeElement($notification);

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Remove Participant entity from collection (one to many).
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Remove Personnage entity from collection (one to many).
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Remove PersonnageBackground entity from collection (one to many).
     */
    public function removePersonnageBackground(PersonnageBackground $personnageBackground): static
    {
        $this->personnageBackgrounds->removeElement($personnageBackground);

        return $this;
    }

    /**
     * Remove Question entity from collection (one to many).
     */
    public function removeQuestion(Question $question): static
    {
        $this->questions->removeElement($question);

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

    public function removeRestriction(Restriction $restriction): static
    {
        /* @phpstan-ignore argument.type */
        $restriction->removeUser($this);
        $this->restrictions->removeElement($restriction);

        return $this;
    }

    /**
     * Remove Restriction entity related by `auteur_id` from collection (one to many).
     */
    public function removeRestrictionRelatedByAuteurId(Restriction $restriction): static
    {
        $this->restrictionRelatedByAuteurIds->removeElement($restriction);

        return $this;
    }

    public function removeRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    public function removeSecondaryGroups(BaseSecondaryGroup $secondaryGroups): static
    {
        if ($this->secondaryGroups->removeElement($secondaryGroups)) {
            // set the owning side to null (unless already changed)
            if ($secondaryGroups->getScenariste() === $this) {
                $secondaryGroups->setScenariste(null);
            }
        }

        return $this;
    }

    public function addQrCodeScanLog(QrCodeScanLog $qrCodeScanLog): static
    {
        if (!$this->qrCodeScanLogs->contains($qrCodeScanLog)) {
            $this->qrCodeScanLogs->add($qrCodeScanLog);
            /* @phpstan-ignore argument.type */
            $qrCodeScanLog->setUser($this);
        }

        return $this;
    }

    public function removeQrCodeScanLog(QrCodeScanLog $qrCodeScanLog): static
    {
        if ($this->qrCodeScanLogs->removeElement($qrCodeScanLog)) {
            // set the owning side to null (unless already changed)
            if ($qrCodeScanLog->getUser() === $this) {
                $qrCodeScanLog->setUser(null);
            }
        }

        return $this;
    }
}
