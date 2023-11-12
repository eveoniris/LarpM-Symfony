<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
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
abstract class BaseUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Assert\NotBlank]
    protected ?int $id = null;

    #[Column(name: 'email', type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    #[Assert\NotBlank]
    protected string $email = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $password = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    protected ?string $username = null;

    #[ORM\Column(type: 'json')]
    protected ?array $roles = [];

    /**
     * @deprecated.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $salt = null;

    /**
     * @deprecated.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected string $rights = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $is_enabled = false;

    #[ORM\Column(name: 'confirmationToken', type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected ?string $confirmationToken = null;

    #[ORM\Column(name: 'timePasswordResetRequested', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    protected ?int $timePasswordResetRequested = null;

    #[ORM\Column(name: 'trombineUrl', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $trombineUrl = null;

    #[Column(name: 'lastConnectionDate', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $lastConnectionDate = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $coeur = 0;

    #[OneToMany(mappedBy: 'user', targetEntity: Background::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $backgrounds;

    #[OneToMany(mappedBy: 'user', targetEntity: Billet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'createur_id', nullable: 'false')]
    protected Collection $billets;

    #[OneToMany(mappedBy: 'user', targetEntity: Debriefing::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $debriefings;

    #[OneToMany(mappedBy: 'user', targetEntity: Document::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $documents;

    #[OneToMany(mappedBy: 'userRelatedByScenaristeId', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'scenariste_id', nullable: 'false')]
    protected Collection $groupeRelatedByScenaristeIds;

    #[OneToMany(mappedBy: 'userRelatedByResponsableId', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: 'false')]
    protected Collection $groupeRelatedByResponsableIds;

    #[OneToMany(mappedBy: 'user', targetEntity: Intrigue::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $intrigues;

    #[OneToMany(mappedBy: 'user', targetEntity: IntrigueHasModification::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $intrigueHasModifications;

    #[OneToMany(mappedBy: 'userRelatedByAuteur', targetEntity: Message::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'auteur', nullable: 'false')]
    protected Collection $messageRelatedByAuteurs;

    #[OneToMany(mappedBy: 'userRelatedByDestinataire', targetEntity: Message::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'destinataire', nullable: 'false')]
    #[OrderBy(['update_date' => 'DESC'])]
    protected Collection $messageRelatedByDestinataires;

    #[OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $notifications;

    #[OneToMany(mappedBy: 'user', targetEntity: Objet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'responsable_id', nullable: 'false')]
    protected Collection $objets;

    #[OneToMany(mappedBy: 'user', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $participants;

    #[OneToMany(mappedBy: 'user', targetEntity: Personnage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $personnages;

    #[OneToMany(mappedBy: 'user', targetEntity: PersonnageBackground::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $personnageBackgrounds;

    #[OneToMany(mappedBy: 'userRelatedByUserId', targetEntity: Post::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $postRelatedByUserIds;

    #[OneToMany(mappedBy: 'user', targetEntity: PostView::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $postViews;

    #[OneToMany(mappedBy: 'user', targetEntity: Question::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $questions;

    #[OneToMany(mappedBy: 'user', targetEntity: Relecture::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $relectures;

    #[OneToMany(mappedBy: 'userRelatedByAuteurId', targetEntity: Restriction::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'auteur_id', nullable: 'false')]
    protected Collection $restrictionRelatedByAuteurIds;

    #[OneToMany(mappedBy: 'user', targetEntity: Rumeur::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $rumeurs;

    #[OneToMany(mappedBy: 'user', targetEntity: Topic::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'user_id', nullable: 'false')]
    protected Collection $topics;

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: EtatCivil::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'etat_civil_id', referencedColumnName: 'id')]
    protected EtatCivil $etatCivil;

    #[ORM\ManyToOne(targetEntity: PersonnageSecondaire::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'personnage_secondaire_id', referencedColumnName: 'id')]
    protected PersonnageSecondaire $personnageSecondaire;

    #[ORM\ManyToOne(targetEntity: Personnage::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected Personnage $personnage;

    #[ORM\ManyToMany(targetEntity: Restriction::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_has_restriction')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'restriction_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $restrictions;

    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'users')]
    protected Collection $posts;

    public function __construct()
    {
        $this->backgrounds = new ArrayCollection();
        $this->billets = new ArrayCollection();
        $this->debriefings = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->groupeRelatedByScenaristeIds = new ArrayCollection();
        $this->groupeRelatedByResponsableIds = new ArrayCollection();
        $this->intrigues = new ArrayCollection();
        $this->intrigueHasModifications = new ArrayCollection();
        $this->messageRelatedByAuteurs = new ArrayCollection();
        $this->messageRelatedByDestinataires = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->objets = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->personnageBackgrounds = new ArrayCollection();
        $this->postRelatedByUserIds = new ArrayCollection();
        $this->postViews = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->relectures = new ArrayCollection();
        $this->restrictionRelatedByAuteurIds = new ArrayCollection();
        $this->rumeurs = new ArrayCollection();
        $this->topics = new ArrayCollection();
        $this->restrictions = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @deprecated
     */
    public function setSalt($salt): static
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @deprecated
     */
    public function getSalt()
    {
        return null;
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

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @deprecated
     */
    public function setRights($rights): static
    {
        $this->rights = $rights;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getUsername();
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
     * Get the value of rights.
     *
     * @return string
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     */
    public function setCreationDate($creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
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

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->is_enabled = $isEnabled;

        return $this;
    }

    /**
     * Get the value of isEnabled.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->is_enabled;
    }

    /**
     * Set the value of confirmationToken.
     *
     * @param string $confirmationToken
     */
    public function setConfirmationToken($confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

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
     * Get the value of timePasswordResetRequested.
     *
     * @return int
     */
    public function getTimePasswordResetRequested()
    {
        return $this->timePasswordResetRequested;
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
     * Get the value of trombineUrl.
     *
     * @return string
     */
    public function getTrombineUrl()
    {
        return $this->trombineUrl;
    }

    /**
     * Set the value of lastConnectionDate.
     *
     * @param \DateTime $lastConnectionDate
     */
    public function setLastConnectionDate($lastConnectionDate): static
    {
        $this->lastConnectionDate = $lastConnectionDate;

        return $this;
    }

    /**
     * Get the value of lastConnectionDate.
     *
     * @return \DateTime
     */
    public function getLastConnectionDate()
    {
        return $this->lastConnectionDate;
    }

    public function setCoeur($coeur): static
    {
        $this->coeur = $coeur;

        return $this;
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

    public function addBackground(Background $background): static
    {
        $this->backgrounds[] = $background;

        return $this;
    }

    public function removeBackground(Background $background): static
    {
        $this->backgrounds->removeElement($background);

        return $this;
    }

    /**
     * Get Background entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBackgrounds()
    {
        return $this->backgrounds;
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
     * Remove Billet entity from collection (one to many).
     */
    public function removeBillet(Billet $billet): static
    {
        $this->billets->removeElement($billet);

        return $this;
    }

    /**
     * Get Billet entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBillets()
    {
        return $this->billets;
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
     * Remove Debriefing entity from collection (one to many).
     */
    public function removeDebriefing(Debriefing $debriefing): static
    {
        $this->debriefings->removeElement($debriefing);

        return $this;
    }

    /**
     * Get Debriefing entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDebriefings()
    {
        return $this->debriefings;
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
     * Remove Document entity from collection (one to many).
     */
    public function removeDocument(Document $document): static
    {
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Get Document entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
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
     * Remove Groupe entity related by `scenariste_id` from collection (one to many).
     */
    public function removeGroupeRelatedByScenaristeId(Groupe $groupe): static
    {
        $this->groupeRelatedByScenaristeIds->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity related by `scenariste_id` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupeRelatedByScenaristeIds()
    {
        return $this->groupeRelatedByScenaristeIds;
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
     * Remove Groupe entity related by `responsable_id` from collection (one to many).
     */
    public function removeGroupeRelatedByResponsableId(Groupe $groupe): static
    {
        $this->groupeRelatedByResponsableIds->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity related by `responsable_id` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupeRelatedByResponsableIds()
    {
        return $this->groupeRelatedByResponsableIds;
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
     * Remove Intrigue entity from collection (one to many).
     */
    public function removeIntrigue(Intrigue $intrigue): static
    {
        $this->intrigues->removeElement($intrigue);

        return $this;
    }

    /**
     * Get Intrigue entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIntrigues()
    {
        return $this->intrigues;
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
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIntrigueHasModifications()
    {
        return $this->intrigueHasModifications;
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
     * Remove Message entity related by `auteur` from collection (one to many).
     */
    public function removeMessageRelatedByAuteur(Message $message): static
    {
        $this->messageRelatedByAuteurs->removeElement($message);

        return $this;
    }

    /**
     * Get Message entity related by `auteur` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessageRelatedByAuteurs()
    {
        return $this->messageRelatedByAuteurs;
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
     * Remove Message entity related by `destinataire` from collection (one to many).
     */
    public function removeMessageRelatedByDestinataire(Message $message): static
    {
        $this->messageRelatedByDestinataires->removeElement($message);

        return $this;
    }

    /**
     * Get Message entity related by `destinataire` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessageRelatedByDestinataires()
    {
        return $this->messageRelatedByDestinataires;
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
     * Remove Notification entity from collection (one to many).
     */
    public function removeNotification(Notification $notification): static
    {
        $this->notifications->removeElement($notification);

        return $this;
    }

    /**
     * Get Notification entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotifications()
    {
        return $this->notifications;
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
     * Remove Objet entity from collection (one to many).
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getObjets()
    {
        return $this->objets;
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
     * Remove Participant entity from collection (one to many).
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Get Participant entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
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
     * Remove Personnage entity from collection (one to many).
     */
    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Get Personnage entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnages()
    {
        return $this->personnages;
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
     * Remove PersonnageBackground entity from collection (one to many).
     */
    public function removePersonnageBackground(PersonnageBackground $personnageBackground): static
    {
        $this->personnageBackgrounds->removeElement($personnageBackground);

        return $this;
    }

    /**
     * Get PersonnageBackground entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageBackgrounds()
    {
        return $this->personnageBackgrounds;
    }

    /**
     * Add Post entity related by `User_id` to collection (one to many).
     */
    public function addPostRelatedByUserId(Post $post): static
    {
        $this->postRelatedByUserIds[] = $post;

        return $this;
    }

    /**
     * Remove Post entity related by `User_id` from collection (one to many).
     */
    public function removePostRelatedByUserId(Post $post): static
    {
        $this->postRelatedByUserIds->removeElement($post);

        return $this;
    }

    /**
     * Get Post entity related by `user_id` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPostRelatedByUserIds()
    {
        return $this->postRelatedByUserIds;
    }

    /**
     * Add PostView entity to collection (one to many).
     */
    public function addPostView(PostView $postView): static
    {
        $this->postViews[] = $postView;

        return $this;
    }

    /**
     * Remove PostView entity from collection (one to many).
     */
    public function removePostView(PostView $postView): static
    {
        $this->postViews->removeElement($postView);

        return $this;
    }

    /**
     * Get PostView entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPostViews(): ArrayCollection
    {
        return $this->postViews;
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
     * Remove Question entity from collection (one to many).
     */
    public function removeQuestion(Question $question): static
    {
        $this->questions->removeElement($question);

        return $this;
    }

    /**
     * Get Question entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Add Relecture entity to collection (one to many).
     */
    public function addRelecture(Relecture $relecture): static
    {
        $this->relectures->add($relecture);

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
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRelectures()
    {
        return $this->relectures;
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
     * Remove Restriction entity related by `auteur_id` from collection (one to many).
     */
    public function removeRestrictionRelatedByAuteurId(Restriction $restriction): static
    {
        $this->restrictionRelatedByAuteurIds->removeElement($restriction);

        return $this;
    }

    /**
     * Get Restriction entity related by `auteur_id` collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRestrictionRelatedByAuteurIds(): ArrayCollection
    {
        return $this->restrictionRelatedByAuteurIds;
    }

    /**
     * Add Rumeur entity to collection (one to many).
     */
    public function addRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->add($rumeur);

        return $this;
    }

    public function removeRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    public function getRumeurs(): ArrayCollection
    {
        return $this->rumeurs;
    }

    public function addTopic(Topic $topic): static
    {
        $this->topics->add($topic);

        return $this;
    }

    public function removeTopic(Topic $topic): static
    {
        $this->topics->removeElement($topic);

        return $this;
    }

    public function getTopics(): ArrayCollection
    {
        return $this->topics;
    }

    public function setEtatCivil(EtatCivil $etatCivil): static
    {
        $this->etatCivil = $etatCivil;

        return $this;
    }

    public function getEtatCivil(): EtatCivil
    {
        return $this->etatCivil;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     */
    public function setPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire = null): static
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     */
    public function getPersonnageSecondaire(): PersonnageSecondaire
    {
        return $this->personnageSecondaire;
    }

    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    public function addRestriction(Restriction $restriction): static
    {
        $restriction->addUser($this);
        $this->restrictions->add($restriction);

        return $this;
    }

    public function removeRestriction(Restriction $restriction): static
    {
        $restriction->removeUser($this);
        $this->restrictions->removeElement($restriction);

        return $this;
    }

    public function getRestrictions(): ArrayCollection
    {
        return $this->restrictions;
    }

    public function addPost(Post $post): static
    {
        $this->posts->add($post);

        return $this;
    }

    public function removePost(Post $post): static
    {
        $this->posts->removeElement($post);

        return $this;
    }

    public function getPosts(): ArrayCollection
    {
        return $this->posts;
    }

    public function __sleep()
    {
        return ['id', 'email', 'password', 'salt', 'rights', 'creation_date', 'username', 'is_enabled', 'confirmationToken', 'timePasswordResetRequested', 'etatCivil', 'trombineUrl', 'personnageSecondaire', 'lastConnectionDate', 'personnage', 'roles'];
    }
}
