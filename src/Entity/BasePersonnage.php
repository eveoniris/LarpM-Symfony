<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PersonnageRepository;
use ArrayIterator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Exception;
use SensitiveParameter;

#[Entity(repositoryClass: PersonnageRepository::class)]
#[ORM\Table(name: 'personnage')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_personnage_groupe1_idx')]
#[ORM\Index(columns: ['classe_id'], name: 'fk_personnage_archetype1_idx')]
#[ORM\Index(columns: ['age_id'], name: 'fk_personnage_age1_idx')]
#[ORM\Index(columns: ['genre_id'], name: 'fk_personnage_genre1_idx')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_personnage_territoire1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_personnage_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnage', 'extended' => 'Personnage'])]
abstract class BasePersonnage
{
    protected int $pugilat;

    #[ORM\Id, Column(type: Types::INTEGER), ORM\GeneratedValue]
    protected int $id;

    #[Column(type: Types::STRING, length: 100)]
    protected string $nom;

    #[Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $surnom = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $intrigue = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $renomme;

    #[Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $photo = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $xp;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $materiel = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $vivant = true;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $age_reel = null;

    #[Column(name: 'trombineUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $trombineUrl = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $richesse = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $heroisme = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $sensible = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $bracelet = null;

    /** @var Collection<int, ExperienceGain> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: ExperienceGain::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $experienceGains;

    /** @var Collection<int, ExperienceUsage> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: ExperienceUsage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $experienceUsages;

    /** @var Collection<int, Trigger> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: Trigger::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: true)]
    protected Collection $triggers;

    /** @var Collection<int, HeroismeHistory> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: HeroismeHistory::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $heroismeHistories;

    /** @var Collection<int, Membre> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: Membre::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $membres;

    /** @var Collection<int, Participant> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $participants;

    /** @var Collection<int, PersonnageBackground> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageBackground::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageBackgrounds;

    /** @var Collection<int, PersonnageHasToken> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageHasToken::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageHasTokens;

    /** @var Collection<int, PersonnageIngredient> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageIngredient::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageIngredients;

    /** @var Collection<int, PersonnageLangues> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageLangues::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    #[OrderBy(['langue' => 'ASC'])]
    protected Collection $personnageLangues;

    /** @var Collection<int, PersonnageRessource> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageRessource::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageRessources;

    /** @var Collection<int, PersonnageTrigger> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageTrigger::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageTriggers;

    /** @var Collection<int, PersonnagesReligions> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnagesReligions::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnagesReligions;

    /** @var Collection<int, Postulant> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: Postulant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $postulants;

    /** @var Collection<int, RenommeHistory> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: RenommeHistory::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $renommeHistories;

    /** @var Collection<int, SecondaryGroup> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: SecondaryGroup::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $secondaryGroups;

    /** @var Collection<int, User> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: User::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $users;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected ?Groupe $groupe;

    #[ManyToOne(targetEntity: Classe::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id', nullable: false)]
    protected Classe $classe;

    #[ManyToOne(targetEntity: Age::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'age_id', referencedColumnName: 'id', nullable: false)]
    protected Age $age;

    #[ManyToOne(targetEntity: Genre::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'genre_id', referencedColumnName: 'id', nullable: false)]
    protected Genre $genre;

    #[ManyToOne(targetEntity: Territoire::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    protected Territoire $territoire;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'personnages')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    /** @var Collection<int, PersonnageHasQuestion> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageHasQuestion::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageHasQuestions;

    /** @var Collection<int, Document> */
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnage_has_document')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'Document_id', referencedColumnName: 'id')]
    #[OrderBy(['code' => 'ASC'])]
    protected Collection $documents;

    /** @var Collection<int, Item> */
    #[ORM\ManyToMany(targetEntity: Item::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnage_has_item')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => 'ASC'])]
    protected Collection $items;

    /** @var Collection<int, Technologie> */
    #[ORM\ManyToMany(targetEntity: Technologie::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnage_has_technologie')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'technologie_id', referencedColumnName: 'id')]
    protected Collection $technologies;

    /** @var Collection<int, Religion> */
    #[ORM\ManyToMany(targetEntity: Religion::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnage_religion_description')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'religion_id', referencedColumnName: 'id')]
    protected Collection $religions;

    /** @var Collection<int, Competence> */
    #[ORM\ManyToMany(targetEntity: Competence::class, mappedBy: 'personnages')]
    // #[ORM\OrderBy(['competenceFamily' => 'ASC', 'level' => 'ASC'])]
    protected Collection $competences;

    /** @var Collection<int, Domaine> */
    #[ORM\ManyToMany(targetEntity: Domaine::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnages_domaines')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'domaine_id', referencedColumnName: 'id')]
    protected Collection $domaines;

    /** @var Collection<int, Potion> */
    #[ORM\ManyToMany(targetEntity: Potion::class, inversedBy: 'personnages', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'personnages_potions')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'potion_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $potions;

    /** @var Collection<int, Priere> */
    #[ORM\ManyToMany(targetEntity: Priere::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnages_prieres')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'priere_id', referencedColumnName: 'id')]
    protected Collection $prieres;

    /** @var Collection<int, Sort> */
    #[ORM\ManyToMany(targetEntity: Sort::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnages_sorts')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'sort_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $sorts;

    /** @var Collection<int, Connaissance> */
    #[ORM\ManyToMany(targetEntity: Connaissance::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnages_connaissances')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'connaissance_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => 'ASC', 'niveau' => 'ASC'])]
    protected Collection $connaissances;

    /** @var Collection<int, PugilatHistory> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PugilatHistory::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $pugilatHistories;

    /** @var Collection<int, PersonnageChronologie> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageChronologie::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    #[OrderBy(['annee' => 'ASC', 'id' => 'ASC'])]
    protected Collection $personnageChronologie;

    /** @var Collection<int, PersonnageLignee> */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageLignee::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_id', nullable: false)]
    protected Collection $personnageLignee;

    /** @var Collection<int, PersonnageLignee> */
    #[OneToMany(mappedBy: 'parent1', targetEntity: PersonnageLignee::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'parent1_id', nullable: false)]
    protected Collection $PersonnageLigneeParent1;

    /** @var Collection<int, PersonnageLignee> */
    #[OneToMany(mappedBy: 'parent2', targetEntity: PersonnageLignee::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'parent2_id', nullable: false)]
    protected Collection $PersonnageLigneeParent2;

    /**
     * @var Collection<int, PersonnageBonus>|null
     */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageBonus::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'personnage_bonus')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    private ?Collection $personnageBonus;

    /**
     * @var Collection<int, Espece>
     */
    #[ORM\ManyToMany(targetEntity: Espece::class, mappedBy: 'personnages')]
    private Collection $especes;

    /**
     * @var Collection<int, PersonnageApprentissage>|null
     */
    #[OneToMany(mappedBy: 'personnage', targetEntity: PersonnageApprentissage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    #[OrderBy(['date_enseignement' => 'ASC', 'id' => 'ASC'])]
    private ?Collection $apprentissages;

    /**
     * @var Collection<int, PersonnageApprentissage>|null
     */
    #[OneToMany(mappedBy: 'enseignant', targetEntity: PersonnageApprentissage::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    private ?Collection $apprentissageEnseignants;

    public function __construct()
    {
        $this->experienceGains = new ArrayCollection();
        $this->experienceUsages = new ArrayCollection();
        $this->heroismeHistories = new ArrayCollection();
        $this->membres = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->personnageBackgrounds = new ArrayCollection();
        $this->personnageHasTokens = new ArrayCollection();
        $this->personnageIngredients = new ArrayCollection();
        $this->personnageLangues = new ArrayCollection();
        $this->personnageRessources = new ArrayCollection();
        $this->personnageTriggers = new ArrayCollection();
        $this->personnagesReligions = new ArrayCollection();
        $this->postulants = new ArrayCollection();
        $this->renommeHistories = new ArrayCollection();
        $this->secondaryGroups = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->technologies = new ArrayCollection();
        $this->religions = new ArrayCollection();
        $this->competences = new ArrayCollection();
        $this->domaines = new ArrayCollection();
        $this->potions = new ArrayCollection();
        $this->prieres = new ArrayCollection();
        $this->sorts = new ArrayCollection();
        $this->connaissances = new ArrayCollection();
        $this->pugilatHistories = new ArrayCollection();
        $this->personnageChronologie = new ArrayCollection();
        $this->personnageLignee = new ArrayCollection();
        $this->personnageBonus = new ArrayCollection();
        $this->especes = new ArrayCollection();
        $this->apprentissages = new ArrayCollection();
        $this->apprentissageEnseignants = new ArrayCollection();
    }

    public function addApprentissage(PersonnageApprentissage $apprentissage): static
    {
        if (!$this->apprentissages->contains($apprentissage)) {
            $this->apprentissages->add($apprentissage);
            /* @phpstan-ignore argument.type */
            $apprentissage->setPersonnage($this);
        }

        return $this;
    }

    /**
     * Add Competence entity to collection.
     */
    public function addCompetence(Competence $competence): static
    {
        $this->competences[] = $competence;

        return $this;
    }

    /**
     * Add Connaissance entity to collection.
     */
    public function addConnaissance(Connaissance $connaissance): static
    {
        /* @phpstan-ignore argument.type */
        $connaissance->addPersonnage($this);
        $this->connaissances[] = $connaissance;

        return $this;
    }

    /**
     * Add Document entity to collection.
     */
    public function addDocument(Document $document): static
    {
        /* @phpstan-ignore argument.type */
        $document->addPersonnage($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Add Domaine entity to collection.
     */
    public function addDomaine(Domaine $domaine): static
    {
        /* @phpstan-ignore argument.type */
        $domaine->addPersonnage($this);
        $this->domaines[] = $domaine;

        return $this;
    }

    public function addEspece(Espece $espece): static
    {
        if (!$this->especes->contains($espece)) {
            $this->especes->add($espece);
            /* @phpstan-ignore argument.type */
            $espece->addPersonnage($this);
        }

        return $this;
    }

    /**
     * Add ExperienceGain entity to collection (one to many).
     */
    public function addExperienceGain(ExperienceGain $experienceGain): static
    {
        $this->experienceGains[] = $experienceGain;

        return $this;
    }

    /**
     * Add ExperienceUsage entity to collection (one to many).
     */
    public function addExperienceUsage(ExperienceUsage $experienceUsage): static
    {
        $this->experienceUsages[] = $experienceUsage;

        return $this;
    }

    /**
     * Add HeroismeHistory entity to collection (one to many).
     */
    public function addHeroismeHistory(HeroismeHistory $heroismeHistory): static
    {
        $this->heroismeHistories[] = $heroismeHistory;

        return $this;
    }

    /**
     * Add Item entity to collection.
     */
    public function addItem(Item $item): static
    {
        /* @phpstan-ignore argument.type */
        $item->addPersonnage($this);
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add Membre entity to collection (one to many).
     */
    public function addMembre(Membre $membre): static
    {
        $this->membres[] = $membre;

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

    public function addPersonnageApprentissage(PersonnageApprentissage $personnageApprentissage): static
    {
        if (!$this->apprentissageEnseignants->contains($personnageApprentissage)) {
            $this->apprentissageEnseignants->add($personnageApprentissage);
            /* @phpstan-ignore argument.type */
            $personnageApprentissage->setEnseignant($this);
        }

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

    public function addPersonnageBonus(PersonnageBonus $personnageBonus): static
    {
        if (!$this->personnageBonus->contains($personnageBonus)) {
            $this->personnageBonus->add($personnageBonus);
            /* @phpstan-ignore argument.type */
            $personnageBonus->setPersonnage($this);
        }

        return $this;
    }

    /**
     * Add PersonnageHasToken entity to collection (one to many).
     */
    public function addPersonnageHasToken(#[SensitiveParameter] PersonnageHasToken $personnageHasToken): static
    {
        $this->personnageHasTokens[] = $personnageHasToken;

        return $this;
    }

    /**
     * Add PersonnageIngredient entity to collection (one to many).
     */
    public function addPersonnageIngredient(PersonnageIngredient $personnageIngredient): static
    {
        $this->personnageIngredients[] = $personnageIngredient;

        return $this;
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
     * Add PersonnageRessource entity to collection (one to many).
     */
    public function addPersonnageRessource(PersonnageRessource $personnageRessource): static
    {
        $this->personnageRessources[] = $personnageRessource;

        return $this;
    }

    /**
     * Add PersonnageTrigger entity to collection (one to many).
     */
    public function addPersonnageTrigger(PersonnageTrigger $personnageTrigger): static
    {
        $this->personnageTriggers[] = $personnageTrigger;

        return $this;
    }

    /**
     * Add PersonnagesReligions entity to collection (one to many).
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Add Postulant entity to collection (one to many).
     */
    public function addPostulant(Postulant $postulant): static
    {
        $this->postulants[] = $postulant;

        return $this;
    }

    /**
     * Add Potion entity to collection.
     */
    public function addPotion(Potion $potion): static
    {
        /* @phpstan-ignore argument.type */
        $potion->addPersonnage($this);
        $this->potions[] = $potion;

        return $this;
    }

    /**
     * Add Priere entity to collection.
     */
    public function addPriere(Priere $priere): static
    {
        $this->prieres[] = $priere;

        return $this;
    }

    /**
     * Add PugilatHistory entity to collection (one to many).
     */
    public function addPugilatHistory(PugilatHistory $pugilatHistory): static
    {
        $this->pugilatHistories[] = $pugilatHistory;

        return $this;
    }

    /**
     * Add Religion entity to collection.
     */
    public function addReligion(Religion $religion): static
    {
        /* @phpstan-ignore argument.type */
        $religion->addPersonnage($this);
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Add RenommeHistory entity to collection (one to many).
     */
    public function addRenommeHistory(RenommeHistory $renommeHistory): static
    {
        $this->renommeHistories[] = $renommeHistory;

        return $this;
    }

    /**
     * Add SecondaryGroup entity to collection (one to many).
     */
    public function addSecondaryGroup(SecondaryGroup $secondaryGroup): static
    {
        $this->secondaryGroups[] = $secondaryGroup;

        return $this;
    }

    /**
     * Add Sort entity to collection.
     */
    public function addSort(Sort $sort): static
    {
        /* @phpstan-ignore argument.type */
        $sort->addPersonnage($this);
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * Add Technologie entity to collection.
     */
    public function addTechnologie(Technologie $technologie): static
    {
        /* @phpstan-ignore argument.type */
        $technologie->addPersonnage($this);
        $this->technologies[] = $technologie;

        return $this;
    }

    /**
     * Add User entity to collection (one to many).
     */
    public function addUser(User $user): static
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Get Age entity (many to one).
     */
    public function getAge(): Age
    {
        return $this->age;
    }

    /**
     * Set Age entity (many to one).
     */
    public function setAge(?Age $age = null): static
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get the value of age_reel.
     */
    public function getAgeReel(): ?int
    {
        return $this->age_reel;
    }

    /**
     * Set the value of age_reel.
     */
    public function setAgeReel(int $age_reel): static
    {
        $this->age_reel = $age_reel;

        return $this;
    }

    /**
     * @return Collection<int, PersonnageApprentissage>
     */
    public function getApprentissages(?bool $withDeleted = false): Collection
    {
        if ($withDeleted) {
            return $this->apprentissages;
        }

        $apprentissages = new ArrayCollection();
        foreach ($this->apprentissages as $apprentissage) {
            if (null !== $apprentissage->getDeletedAt()) {
                continue;
            }

            $apprentissages->add($apprentissage);
        }

        return $apprentissages;
    }

    /**
     * Get Classe entity (many to one).
     */
    public function getClasse(): Classe
    {
        return $this->classe;
    }

    /**
     * Set Classe entity (many to one).
     */
    public function setClasse(?Classe $classe = null): static
    {
        $this->classe = $classe;

        return $this;
    }

    /** @return Collection<int, Competence> */
    public function getCompentecesByFamilyLevel(): Collection
    {
        return $this->competences;
    }

    /**
     * @return Collection<int, Competence>
     *
     * @throws Exception
     */
    public function getCompetences(): Collection
    {
        $all = new ArrayCollection();
        $families = [];
        /** @var Competence $competence */
        foreach ($this->competences as $competence) {
            if (!$competence->getCompetenceFamily() || !$competence->getLevel()) {
                continue;
            }

            $key = $competence->getCompetenceFamily()->getLabel();
            $level = $competence->getLevel()->getIndex();
            $families[$key][$level] = $competence;
        }

        foreach ($families as &$family) {
            ksort($family);
        }
        unset($family);

        /*
         * $iterator = $this->competences->getIterator();
         * $iterator->uasort(static function (Competence $a, Competence $b): int {
         * $aC = $a->getCompetenceFamily()?->getLabel().'_'.$a->getLevel()?->getIndex();
         * $bC = $b->getCompetenceFamily()?->getLabel().'_'.$b->getLevel()?->getIndex();
         *
         * return $aC <=> $bC;
         * });
         *
         *
         * return new ArrayCollection(iterator_to_array($iterator));
         */
        ksort($families);

        foreach ($families as $family) {
            foreach ($family as $competence) {
                $all->add($competence);
            }
        }

        return $all;
    }

    /**
     * Get Connaissance entity collection.
     */
    /** @return Collection<int, Connaissance> */
    public function getConnaissances(): Collection
    {
        return $this->connaissances;
    }

    /**
     * Get Document entity collection.
     */
    /** @return Collection<int, Document> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Get Domaine entity collection.
     */
    /** @return Collection<int, Domaine> */
    public function getDomaines(): Collection
    {
        return $this->domaines;
    }

    /**
     * @return Collection<int, Espece>
     */
    public function getEspeces(): Collection
    {
        return $this->especes;
    }

    /**
     * Get ExperienceGain entity collection (one to many).
     */
    /** @return Collection<int, ExperienceGain> */
    public function getExperienceGains(): Collection
    {
        return $this->experienceGains;
    }

    /**
     * Get ExperienceUsage entity collection (one to many).
     */
    /** @return Collection<int, ExperienceUsage> */
    public function getExperienceUsages(): Collection
    {
        return $this->experienceUsages;
    }

    /**
     * Get Genre entity (many to one).
     */
    public function getGenre(): Genre
    {
        return $this->genre;
    }

    /**
     * Set Genre entity (many to one).
     */
    public function setGenre(?Genre $genre = null): static
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    /**
     * Set Groupe entity (many to one).
     */
    public function setGroupe(?Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get the value of heroisme.
     */
    public function getHeroisme(): int
    {
        // return $this->heroisme;
        return 0;
    }

    /**
     * Set the value of heroisme.
     */
    public function setHeroisme(int $heroisme): static
    {
        $this->heroisme = $heroisme;

        return $this;
    }

    /**
     * Get HeroismeHistory entity collection (one to many).
     */
    /** @return Collection<int, HeroismeHistory> */
    public function getHeroismeHistories(): Collection
    {
        return $this->heroismeHistories;
    }

    /**
     * Get the value of intrigue.
     */
    public function getIntrigue(): bool
    {
        return $this->intrigue ?? false;
    }

    /**
     * Set the value of intrigue.
     */
    public function setIntrigue(bool $intrigue): static
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Item entity collection.
     */
    /** @return Collection<int, Item> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @return array<int, string> */
    public function getLangueMateriel(): array
    {
        $langueMateriel = [];
        foreach ($this->getPersonnageLangues() as $langue) {
            if (!($langue->getLangue()->getGroupeLangue()->getId() > 0 && $langue->getLangue()->getGroupeLangue()->getId() < 6)) {
                continue;
            }

            if (!\in_array('Bracelet ' . $langue->getLangue()->getGroupeLangue()->getCouleur(), $langueMateriel)) {
                $langueMateriel[] = 'Bracelet ' . $langue->getLangue()->getGroupeLangue()->getCouleur();
            }

            if (0 === $langue->getLangue()->getDiffusion()) {
                $langueMateriel[] = 'Alphabet ' . $langue->getLangue()->getLabel();
            }
        }

        sort($langueMateriel);

        return $langueMateriel;
    }

    /**
     * Get PersonnageLangues entity collection (one to many).
     */
    /** @return Collection<int, PersonnageLangues> */
    public function getPersonnageLangues(): Collection
    {
        return $this->personnageLangues;
    }

    /**
     * Get the value of id.
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
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
     * Get the value of materiel.
     */
    public function getMateriel(): string
    {
        return $this->materiel ?? '';
    }

    /**
     * Set the value of materiel.
     */
    public function setMateriel(string $materiel): static
    {
        $this->materiel = $materiel;

        return $this;
    }

    /**
     * Get Membre entity collection (one to many).
     */
    /** @return Collection<int, Membre> */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    /**
     * Get the value of nom.
     */
    public function getNom(): string
    {
        return $this->nom ?? '';
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
     * Get Participant entity collection (one to many).
     */
    /** @return Collection<int, Participant> */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * Get PersonnageBackground entity collection (one to many).
     */
    /** @return Collection<int, PersonnageBackground> */
    public function getPersonnageBackgrounds(): Collection
    {
        return $this->personnageBackgrounds;
    }

    /** @return Collection<int, PersonnageBonus> */
    public function getPersonnageBonus(): ?Collection
    {
        return $this->personnageBonus;
    }

    /**
     * Get personnageChronologie entity collection.
     */
    /** @return Collection<int, PersonnageChronologie> */
    public function getPersonnageChronologie(): Collection
    {
        return $this->personnageChronologie;
    }

    /** @return Collection<int, PersonnageHasQuestion> */
    public function getPersonnageHasQuestions(): Collection
    {
        return $this->personnageHasQuestions;
    }

    /**
     * @param Collection<int, PersonnageHasQuestion> $personnageHasQuestions
     */
    public function setPersonnageHasQuestions(Collection $personnageHasQuestions): static
    {
        $this->personnageHasQuestions = $personnageHasQuestions;

        return $this;
    }

    /**
     * Get PersonnageHasToken entity collection (one to many).
     */
    /** @return Collection<int, PersonnageHasToken> */
    public function getPersonnageHasTokens(): Collection
    {
        return $this->personnageHasTokens;
    }

    /**
     * Get PersonnageIngredient entity collection (one to many).
     */
    /** @return Collection<int, PersonnageIngredient> */
    public function getPersonnageIngredients(): Collection
    {
        return $this->personnageIngredients;
    }

    /**
     * Get personnageLignee entity collection.
     */
    /** @return Collection<int, PersonnageLignee> */
    public function getPersonnageLignee(): Collection
    {
        return $this->personnageLignee;
    }

    /**
     * Get PersonnageRessource entity collection (one to many).
     */
    /** @return Collection<int, PersonnageRessource> */
    public function getPersonnageRessources(): Collection
    {
        return $this->personnageRessources;
    }

    /**
     * Get PersonnageTrigger entity collection (one to many).
     */
    /** @return Collection<int, PersonnageTrigger> */
    public function getPersonnageTriggers(): Collection
    {
        return $this->personnageTriggers;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     */
    /** @return Collection<int, PersonnagesReligions> */
    public function getPersonnagesReligions(): Collection
    {
        /** @var ArrayIterator<int, mixed> $iterator */
        $iterator = $this->personnagesReligions->getIterator();
        $iterator->uasort(static fn (PersonnagesReligions $a, PersonnagesReligions $b): int => $a->getReligionLevel() <=> $b->getReligionLevel());

        return new ArrayCollection(iterator_to_array($iterator));

        // return $this->personnagesReligions;
    }

    /**
     * Get the value of photo.
     */
    public function getPhoto(): string
    {
        return $this->photo ?? '';
    }

    /**
     * Set the value of photo.
     */
    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get Postulant entity collection (one to many).
     */
    /** @return Collection<int, Postulant> */
    public function getPostulants(): Collection
    {
        return $this->postulants;
    }

    /**
     * Get Potion entity collection.
     */
    /** @return Collection<int, Potion> */
    public function getPotions(): Collection
    {
        return $this->potions;
    }

    /**
     * Get Priere entity collection.
     */
    /** @return Collection<int, Priere> */
    public function getPrieres(): Collection
    {
        return $this->prieres;
    }

    /**
     * Get PugilatHistory entity collection (one to many).
     */
    /** @return Collection<int, PugilatHistory> */
    public function getPugilatHistories(): Collection
    {
        return $this->pugilatHistories;
    }

    /**
     * Get Religion entity collection.
     */
    /** @return Collection<int, Religion> */
    public function getReligions(): Collection
    {
        return $this->religions;
    }

    /**
     * Get the value of renomme.
     */
    public function getRenomme(): int
    {
        return $this->renomme;
    }

    /**
     * Set the value of renomme.
     */
    public function setRenomme(int $renomme): static
    {
        $this->renomme = $renomme;

        return $this;
    }

    /**
     * Get RenommeHistory entity collection (one to many).
     */
    /** @return Collection<int, RenommeHistory> */
    public function getRenommeHistories(): Collection
    {
        return $this->renommeHistories;
    }

    /**
     * Get the value of richesse.
     */
    public function getRichesse(): ?int
    {
        return $this->richesse ?? 0;
    }

    /**
     * Set the value of richesse.
     */
    public function setRichesse(int $richesse): static
    {
        $this->richesse = $richesse;

        return $this;
    }

    /**
     * Get SecondaryGroup entity collection (one to many).
     */
    /** @return Collection<int, SecondaryGroup> */
    public function getSecondaryGroups(): Collection
    {
        return $this->secondaryGroups;
    }

    /**
     * Get the value of sensible.
     */
    public function getSensible(): ?bool
    {
        return $this->sensible;
    }

    /**
     * Set the value of sensible.
     */
    public function setSensible(bool $sensible): static
    {
        $this->sensible = $sensible;

        return $this;
    }

    /**
     * Get Sort entity collection.
     */
    /** @return Collection<int, Sort> */
    public function getSorts(): Collection
    {
        return $this->sorts;
    }

    /**
     * Get the value of surnom.
     */
    public function getSurnom(): string
    {
        return $this->surnom ?? '';
    }

    /**
     * Set the value of surnom.
     */
    public function setSurnom(?string $surnom): static
    {
        $this->surnom = $surnom;

        return $this;
    }

    /**
     * Get Technologie entity collection.
     */
    /** @return Collection<int, Technologie> */
    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    /**
     * Get Territoire entity (many to one).
     */
    public function getTerritoire(): ?Territoire
    {
        return $this->territoire ?? null;
    }

    /**
     * Set Territoire entity (many to one).
     */
    public function setTerritoire(?Territoire $territoire = null): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get the value of trombineUrl.
     */
    public function getTrombineUrl(): string
    {
        return $this->trombineUrl ?? '';
    }

    /**
     * Set the value of trombineUrl.
     */
    public function setTrombineUrl(string $trombineUrl): static
    {
        $this->trombineUrl = $trombineUrl;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User entity collection (one to many).
     */
    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Get the value of vivant.
     */
    public function getVivant(): bool
    {
        return $this->vivant;
    }

    /**
     * Set the value of vivant.
     */
    public function setVivant(bool $vivant): static
    {
        $this->vivant = $vivant;

        return $this;
    }

    /**
     * Get the value of xp.
     */
    public function getXp(): int
    {
        return $this->xp;
    }

    /**
     * Set the value of xp.
     */
    public function setXp(int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }

    /**
     * Get Competence entity collection.
     */

    /**
     * @return Collection<int, PersonnageApprentissage>
     */
    public function getapprentissageEnseignants(): Collection
    {
        return $this->apprentissageEnseignants;
    }

    public function isBracelet(): bool
    {
        return (bool) $this->bracelet;
    }

    public function isBraceletSetted(): bool
    {
        return null !== $this->bracelet;
    }

    public function isSensibleSetted(): bool
    {
        return null !== $this->sensible;
    }

    public function prochaineParticipation(): Participant
    {
        $now = new DateTime();
        $prochain = null;

        foreach ($this->participants as $p) {
            if (!($now < $p->getGn()->getDateFin() && (null === $prochain || $prochain->getGn()->getDateDebut() > $p->getGn()->getDateDebut()))) {
                continue;
            }

            $prochain = $p;
        }

        return $prochain;
    }

    public function removeApprentissage(PersonnageApprentissage $apprentissage): static
    {
        if ($this->apprentissages->removeElement($apprentissage)) {
            // set the owning side to null (unless already changed)
            if ($apprentissage->getPersonnage() === $this) {
                $apprentissage->setPersonnage(null);
            }
        }

        return $this;
    }

    /**
     * Remove Competence entity from collection.
     */
    public function removeCompetence(Competence $competence): static
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    /**
     * Remove Connaissance entity from collection.
     */
    public function removeConnaissance(Connaissance $connaissance): static
    {
        /* @phpstan-ignore argument.type */
        $connaissance->removePersonnage($this);
        $this->connaissances->removeElement($connaissance);

        return $this;
    }

    /**
     * Remove Document entity from collection.
     */
    public function removeDocument(Document $document): static
    {
        /* @phpstan-ignore argument.type */
        $document->removePersonnage($this);
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Remove Domaine entity from collection.
     */
    public function removeDomaine(Domaine $domaine): static
    {
        /* @phpstan-ignore argument.type */
        $domaine->removePersonnage($this);
        $this->domaines->removeElement($domaine);

        return $this;
    }

    public function removeEspece(Espece $espece): static
    {
        if ($this->especes->removeElement($espece)) {
            /* @phpstan-ignore argument.type */
            $espece->removePersonnage($this);
        }

        return $this;
    }

    /**
     * Remove ExperienceGain entity from collection (one to many).
     */
    public function removeExperienceGain(ExperienceGain $experienceGain): static
    {
        $this->experienceGains->removeElement($experienceGain);

        return $this;
    }

    /**
     * Remove ExperienceUsage entity from collection (one to many).
     */
    public function removeExperienceUsage(ExperienceUsage $experienceUsage): static
    {
        $this->experienceUsages->removeElement($experienceUsage);

        return $this;
    }

    /**
     * Remove HeroismeHistory entity from collection (one to many).
     */
    public function removeHeroismeHistory(HeroismeHistory $heroismeHistory): static
    {
        $this->heroismeHistories->removeElement($heroismeHistory);

        return $this;
    }

    /**
     * Remove Item entity from collection.
     */
    public function removeItem(Item $item): static
    {
        /* @phpstan-ignore argument.type */
        $item->removePersonnage($this);
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Remove Membre entity from collection (one to many).
     */
    public function removeMembre(Membre $membre): static
    {
        $this->membres->removeElement($membre);

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

    public function removePersonnageApprentissage(PersonnageApprentissage $personnageApprentissage): static
    {
        if ($this->apprentissageEnseignants->removeElement($personnageApprentissage)) {
            // set the owning side to null (unless already changed)
            if ($personnageApprentissage->getEnseignant() === $this) {
                $personnageApprentissage->setEnseignant(null);
            }
        }

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

    public function removePersonnageBonus(PersonnageBonus $personnageBonus): static
    {
        if ($this->personnageBonus->removeElement($personnageBonus)) {
            // set the owning side to null (unless already changed)
            if ($personnageBonus->getPersonnage() === $this) {
                $personnageBonus->setPersonnage(null);
            }
        }

        return $this;
    }

    /**
     * Remove PersonnageHasToken entity from collection (one to many).
     */
    public function removePersonnageHasToken(#[SensitiveParameter] PersonnageHasToken $personnageHasToken): static
    {
        $this->personnageHasTokens->removeElement($personnageHasToken);

        return $this;
    }

    /**
     * Remove PersonnageIngredient entity from collection (one to many).
     */
    public function removePersonnageIngredient(PersonnageIngredient $personnageIngredient): static
    {
        $this->personnageIngredients->removeElement($personnageIngredient);

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
     * Remove PersonnageRessource entity from collection (one to many).
     */
    public function removePersonnageRessource(PersonnageRessource $personnageRessource): static
    {
        $this->personnageRessources->removeElement($personnageRessource);

        return $this;
    }

    /**
     * Remove PersonnageTrigger entity from collection (one to many).
     */
    public function removePersonnageTrigger(PersonnageTrigger $personnageTrigger): static
    {
        $this->personnageTriggers->removeElement($personnageTrigger);

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions): static
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Remove Postulant entity from collection (one to many).
     */
    public function removePostulant(Postulant $postulant): static
    {
        $this->postulants->removeElement($postulant);

        return $this;
    }

    /**
     * Remove Potion entity from collection.
     */
    public function removePotion(Potion $potion): static
    {
        /* @phpstan-ignore argument.type */
        $potion->removePersonnage($this);
        $this->potions->removeElement($potion);

        return $this;
    }

    /**
     * Remove Priere entity from collection.
     */
    public function removePriere(Priere $priere): static
    {
        $this->prieres->removeElement($priere);

        return $this;
    }

    /**
     * Remove PugilatHistory entity from collection (one to many).
     */
    public function removePugilatHistory(PugilatHistory $pugilatHistory): static
    {
        $this->pugilatHistories->removeElement($pugilatHistory);

        return $this;
    }

    /**
     * Remove Religion entity from collection.
     */
    public function removeReligion(Religion $religion): static
    {
        /* @phpstan-ignore argument.type */
        $religion->removePersonnage($this);
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Remove RenommeHistory entity from collection (one to many).
     */
    public function removeRenommeHistory(RenommeHistory $renommeHistory): static
    {
        $this->renommeHistories->removeElement($renommeHistory);

        return $this;
    }

    /**
     * Remove SecondaryGroup entity from collection (one to many).
     */
    public function removeSecondaryGroup(SecondaryGroup $secondaryGroup): static
    {
        $this->secondaryGroups->removeElement($secondaryGroup);

        return $this;
    }

    /**
     * Remove Sort entity from collection.
     */
    public function removeSort(Sort $sort): static
    {
        /* @phpstan-ignore argument.type */
        $sort->removePersonnage($this);
        $this->sorts->removeElement($sort);

        return $this;
    }

    /**
     * Remove Technologie entity from collection.
     */
    public function removeTechnologie(Technologie $technologie): static
    {
        /* @phpstan-ignore argument.type */
        $technologie->removePersonnage($this);
        $this->technologies->removeElement($technologie);

        return $this;
    }

    /**
     * Remove User entity from collection (one to many).
     */
    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function setBracelet(?bool $bracelet): static
    {
        $this->bracelet = (bool) $bracelet;

        return $this;
    }

    /**
     * Set the value of pugilat.
     */
    public function setPugilat(int $pugilat): static
    {
        $this->pugilat = $pugilat;

        return $this;
    }
}
