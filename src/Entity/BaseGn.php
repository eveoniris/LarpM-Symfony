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
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Service\Attribute\Required;

#[Entity]
#[ORM\Table(name: 'gn')]
#[ORM\Index(columns: ['topic_id'], name: 'fk_gn_topic1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGn', 'extended' => 'Gn'])]
class BaseGn
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Required]
    protected string $label = '';

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $xp_creation = null;

    #[Column(name: 'date_jeu', type: Types::INTEGER, nullable: true)]
    protected ?int $date_jeu = null;

    #[Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Required]
    protected ?string $description = '';

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date_debut = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date_fin = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date_installation_joueur = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $date_fin_orga = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $adresse = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $actif;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $billetterie = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $conditions_inscription = null;

    #[OneToMany(mappedBy: 'gn', targetEntity: Annonce::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $annonces;

    #[OneToMany(mappedBy: 'gn', targetEntity: Background::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $backgrounds;

    #[OneToMany(mappedBy: 'gn', targetEntity: Billet::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $billets;

    #[OneToMany(mappedBy: 'gn', targetEntity: Debriefing::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $debriefings;

    #[OneToMany(mappedBy: 'gn', targetEntity: GroupeGn::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $groupeGns;

    #[OneToMany(mappedBy: 'gn', targetEntity: Participant::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $participants;

    #[OneToMany(mappedBy: 'gn', targetEntity: PersonnageBackground::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $personnageBackgrounds;

    #[OneToMany(mappedBy: 'gn', targetEntity: Rumeur::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'gn_id', nullable: 'false')]
    protected Collection $rumeurs;

    #[ORM\ManyToOne(targetEntity: Topic::class, cascade: ['persist'], inversedBy: 'gns')]
    #[JoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    protected Topic $topic;

    public function __construct()
    {
        $this->annonces = new ArrayCollection();
        $this->backgrounds = new ArrayCollection();
        $this->billets = new ArrayCollection();
        $this->debriefings = new ArrayCollection();
        $this->groupeGns = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->personnageBackgrounds = new ArrayCollection();
        $this->rumeurs = new ArrayCollection();
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

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setXpCreation(int $xp_creation): static
    {
        $this->xp_creation = $xp_creation;

        return $this;
    }

    public function getXpCreation(): int
    {
        return $this->xp_creation ?? 0;
    }

    public function setDateJeu(?int $date_jeu): static
    {
        $this->date_jeu = $date_jeu;

        return $this;
    }

    public function getDateJeu(): ?int
    {
        return $this->date_jeu;
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

    public function setDateDebut(?\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateInstallationJoueur(?\DateTime $date_installation_joueur): static
    {
        $this->date_installation_joueur = $date_installation_joueur;

        return $this;
    }

    public function getDateInstallationJoueur(): ?\DateTime
    {
        return $this->date_installation_joueur;
    }

    public function setDateFinOrga(?\DateTime $date_fin_orga): static
    {
        $this->date_fin_orga = $date_fin_orga;

        return $this;
    }

    public function getDateFinOrga(): ?\DateTime
    {
        return $this->date_fin_orga;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getAdresse(): string
    {
        return $this->adresse ?? '';
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getActif(): bool
    {
        return $this->actif;
    }

    public function setBilletterie(?string $billetterie): static
    {
        $this->billetterie = $billetterie;

        return $this;
    }

    public function getBilletterie(): string
    {
        return $this->billetterie ?? '';
    }

    public function setConditionsInscription(?string $conditions_inscription): static
    {
        $this->conditions_inscription = $conditions_inscription;

        return $this;
    }

    public function getConditionsInscription(): string
    {
        return $this->conditions_inscription ?? '';
    }

    public function addAnnonce(Annonce $annonce): static
    {
        $this->annonces[] = $annonce;

        return $this;
    }

    public function removeAnnonce(Annonce $annonce): static
    {
        $this->annonces->removeElement($annonce);

        return $this;
    }

    public function getAnnonces(): Collection
    {
        return $this->annonces;
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

    public function getBackgrounds(): Collection
    {
        return $this->backgrounds;
    }

    public function addBillet(Billet $billet): static
    {
        $this->billets[] = $billet;

        return $this;
    }

    public function removeBillet(Billet $billet): static
    {
        $this->billets->removeElement($billet);

        return $this;
    }

    public function getBillets(): Collection
    {
        return $this->billets;
    }

    public function addDebriefing(Debriefing $debriefing): static
    {
        $this->debriefings[] = $debriefing;

        return $this;
    }

    public function removeDebriefing(Debriefing $debriefing): static
    {
        $this->debriefings->removeElement($debriefing);

        return $this;
    }

    public function getDebriefings(): Collection
    {
        return $this->debriefings;
    }

    public function addGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns[] = $groupeGn;

        return $this;
    }

    public function removeGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns->removeElement($groupeGn);

        return $this;
    }

    public function getGroupeGns(): Collection
    {
        return $this->groupeGns;
    }

    public function addParticipant(Participant $participant): static
    {
        $this->participants[] = $participant;

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addPersonnageBackground(PersonnageBackground $personnageBackground): static
    {
        $this->personnageBackgrounds[] = $personnageBackground;

        return $this;
    }

    public function removePersonnageBackground(PersonnageBackground $personnageBackground): static
    {
        $this->personnageBackgrounds->removeElement($personnageBackground);

        return $this;
    }

    public function getPersonnageBackgrounds(): Collection
    {
        return $this->personnageBackgrounds;
    }

    public function addRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs[] = $rumeur;

        return $this;
    }

    public function removeRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    public function getRumeurs(): Collection
    {
        return $this->rumeurs;
    }

    public function setTopic(Topic $topic = null): static
    {
        $this->topic = $topic;

        return $this;
    }

    public function getTopic(): Topic
    {
        return $this->topic;
    }

    /* public function __sleep()
    {
        return ['id', 'label', 'xp_creation', 'description', 'date_debut', 'date_fin', 'date_installation_joueur', 'date_fin_orga', 'adresse', 'topic_id', 'actif', 'billetterie', 'conditions_inscription'];
    } */
}
