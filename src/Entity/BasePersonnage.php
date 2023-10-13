<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\Personnage
 *
 * @Table(name="personnage", indexes={@Index(name="fk_personnage_groupe1_idx", columns={"groupe_id"}), @Index(name="fk_personnage_archetype1_idx", columns={"classe_id"}), @Index(name="fk_personnage_age1_idx", columns={"age_id"}), @Index(name="fk_personnage_genre1_idx", columns={"genre_id"}), @Index(name="fk_personnage_territoire1_idx", columns={"territoire_id"}), @Index(name="fk_personnage_User1_idx", columns={"User_id"})})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BasePersonnage", "extended":"Personnage"})
 */
class BasePersonnage
{
    #[ORM\Id, ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER), ORM\GeneratedValue]
    protected int $id;

    /**
     * @Column(type="string", length=100)
     */
    protected $nom;

    /**
     * @Column(type="string", length=100, nullable=true)
     */
    protected $surnom;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $intrigue;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $renomme;

    /**
     * @Column(type="string", length=100, nullable=true)
     */
    protected $photo;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $xp;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $materiel;

    /**
     * @Column(type="boolean")
     */
    protected $vivant;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $age_reel;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $trombineUrl;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $richesse;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $heroisme;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $sensible;    

    /**
     * @OneToMany(targetEntity="ExperienceGain", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $experienceGains;

    /**
     * @OneToMany(targetEntity="ExperienceUsage", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $experienceUsages;

    /**
     * @OneToMany(targetEntity="HeroismeHistory", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $heroismeHistories;

    /**
     * @OneToMany(targetEntity="Membre", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $membres;

    /**
     * @OneToMany(targetEntity="Participant", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $participants;

    /**
     * @OneToMany(targetEntity="PersonnageBackground", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageBackgrounds;

    /**
     * @OneToMany(targetEntity="PersonnageHasToken", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageHasTokens;

    /**
     * @OneToMany(targetEntity="PersonnageIngredient", mappedBy="personnage", cascade={"persist", "remove"})
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageIngredients;

    /**
     * @OneToMany(targetEntity="PersonnageLangues", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     * @OrderBy({"langue" = "ASC"})
     */
    protected $personnageLangues;

    /**
     * @OneToMany(targetEntity="PersonnageRessource", mappedBy="personnage", cascade={"persist", "remove"})
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageRessources;

    /**
     * @OneToMany(targetEntity="PersonnageTrigger", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageTriggers;

    /**
     * @OneToMany(targetEntity="PersonnagesReligions", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnagesReligions;

    /**
     * @OneToMany(targetEntity="Postulant", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $postulants;

    /**
     * @OneToMany(targetEntity="RenommeHistory", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $renommeHistories;

    /**
     * @OneToMany(targetEntity="SecondaryGroup", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $secondaryGroups;

    /**
     * @OneToMany(targetEntity="User", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $Users;

    /**
     * @ManyToOne(targetEntity="Groupe", inversedBy="personnages")
     * @JoinColumn(name="groupe_id", referencedColumnName="id")
     */
    protected $groupe;

    /**
     * @ManyToOne(targetEntity="Classe", inversedBy="personnages")
     * @JoinColumn(name="classe_id", referencedColumnName="id", nullable=false)
     */
    protected $classe;

    /**
     * @ManyToOne(targetEntity="Age", inversedBy="personnages")
     * @JoinColumn(name="age_id", referencedColumnName="id", nullable=false)
     */
    protected $age;

    /**
     * @ManyToOne(targetEntity="Genre", inversedBy="personnages")
     * @JoinColumn(name="genre_id", referencedColumnName="id", nullable=false)
     */
    protected $genre;

    /**
     * @ManyToOne(targetEntity="Territoire", inversedBy="personnages")
     * @JoinColumn(name="territoire_id", referencedColumnName="id")
     */
    protected $territoire;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="personnages")
     * @JoinColumn(name="User_id", referencedColumnName="id")
     */
    protected $User;

    /**
     * @ManyToMany(targetEntity="Document", inversedBy="personnages")
     * @JoinTable(name="personnage_has_document",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="Document_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"code" = "ASC",})
     */
    protected $documents;

    /**
     * @ManyToMany(targetEntity="Item", inversedBy="personnages")
     * @JoinTable(name="personnage_has_item",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="item_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC",})
     */
    protected $items;

    /**
     * @ManyToMany(targetEntity="Technologie", inversedBy="personnages")
     * @JoinTable(name="personnage_has_technologie",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="technologie_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC",})
     */
    protected $technologies;

    /**
     * @ManyToMany(targetEntity="Religion", inversedBy="personnages")
     * @JoinTable(name="personnage_religion_description",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="religion_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC",})
     */
    protected $religions;

    /**
     * @ManyToMany(targetEntity="Competence", mappedBy="personnages")
     * @OrderBy({"competenceFamily" = "ASC", "level" = "ASC"})
     */
    protected $competences;

    /**
     * @ManyToMany(targetEntity="Domaine", inversedBy="personnages")
     * @JoinTable(name="personnages_domaines",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="domaine_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC",})
     */
    protected $domaines;

    /**
     * @ManyToMany(targetEntity="Potion", inversedBy="personnages")
     * @JoinTable(name="personnages_potions",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="potion_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC", "niveau" = "ASC",})
     */
    protected $potions;

    /**
     * @ManyToMany(targetEntity="Priere", mappedBy="personnages")
     * @JoinTable(name="personnages_prieres",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="priere_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"sphere" = "ASC", "niveau" = "ASC",})
     */
    protected $prieres;

    /**
     * @ManyToMany(targetEntity="Sort", inversedBy="personnages")
     * @JoinTable(name="personnages_sorts",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="sort_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC", "niveau" = "ASC",})
     */
    protected $sorts;

    /**
     * @ManyToMany(targetEntity="Connaissance", inversedBy="personnages")
     * @JoinTable(name="personnages_connaissances",
     *     joinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="connaissance_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC", "niveau" = "ASC",})
     */
    protected $connaissances;

    /**
     * @OneToMany(targetEntity="PugilatHistory", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $pugilatHistories;

    /**
     * @OneToMany(targetEntity="PersonnageChronologie", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     * @OrderBy({"annee" = "ASC", "id" = "ASC",})
     */
    protected $personnageChronologie;

    /**
     * @OneToMany(targetEntity="PersonnageLignee", mappedBy="personnage")
     * @JoinColumn(name="id", referencedColumnName="personnage_id", nullable=false)
     */
    protected $personnageLignee;

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
        $this->Users = new ArrayCollection();
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
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\Personnage
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of nom.
     *
     * @param string $nom
     * @return \App\Entity\Personnage
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set the value of surnom.
     *
     * @param string $surnom
     * @return \App\Entity\Personnage
     */
    public function setSurnom($surnom)
    {
        $this->surnom = $surnom;

        return $this;
    }

    /**
     * Get the value of surnom.
     *
     * @return string
     */
    public function getSurnom()
    {
        return $this->surnom;
    }

    /**
     * Set the value of intrigue.
     *
     * @param boolean $intrigue
     * @return \App\Entity\Personnage
     */
    public function setIntrigue($intrigue)
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get the value of intrigue.
     *
     * @return boolean
     */
    public function getIntrigue()
    {
        return $this->intrigue;
    }

    /**
     * Set the value of sensible.
     *
     * @param boolean $sensible
     * @return \App\Entity\Personnage
     */
    public function setSensible($sensible)
    {
        $this->sensible = $sensible;

        return $this;
    }

    /**
     * Get the value of sensible.
     *
     * @return boolean
     */
    public function getSensible()
    {
        return $this->sensible;
    }

    /**
     * Set the value of renomme.
     *
     * @param integer $renomme
     * @return \App\Entity\Personnage
     */
    public function setRenomme($renomme)
    {
        $this->renomme = $renomme;

        return $this;
    }

    /**
     * Get the value of renomme.
     *
     * @return integer
     */
    public function getRenomme()
    {
        return $this->renomme;
    }

    /**
     * Set the value of photo.
     *
     * @param string $photo
     * @return \App\Entity\Personnage
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get the value of photo.
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set the value of xp.
     *
     * @param integer $xp
     * @return \App\Entity\Personnage
     */
    public function setXp($xp)
    {
        $this->xp = $xp;

        return $this;
    }

    /**
     * Get the value of xp.
     *
     * @return integer
     */
    public function getXp()
    {
        return $this->xp;
    }

    /**
     * Set the value of materiel.
     *
     * @param string $materiel
     * @return \App\Entity\Personnage
     */
    public function setMateriel($materiel)
    {
        $this->materiel = $materiel;

        return $this;
    }

    /**
     * Get the value of materiel.
     *
     * @return string
     */
    public function getMateriel()
    {
        return $this->materiel;
    }

    /**
     * Set the value of vivant.
     *
     * @param boolean $vivant
     * @return \App\Entity\Personnage
     */
    public function setVivant($vivant)
    {
        $this->vivant = $vivant;

        return $this;
    }

    /**
     * Get the value of vivant.
     *
     * @return boolean
     */
    public function getVivant()
    {
        return $this->vivant;
    }

    /**
     * Set the value of age_reel.
     *
     * @param integer $age_reel
     * @return \App\Entity\Personnage
     */
    public function setAgeReel($age_reel)
    {
        $this->age_reel = $age_reel;

        return $this;
    }

    /**
     * Get the value of age_reel.
     *
     * @return integer
     */
    public function getAgeReel()
    {
        return $this->age_reel;
    }

    /**
     * Set the value of trombineUrl.
     *
     * @param string $trombineUrl
     * @return \App\Entity\Personnage
     */
    public function setTrombineUrl($trombineUrl)
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
     * Set the value of richesse.
     *
     * @param integer $richesse
     * @return \App\Entity\Personnage
     */
    public function setRichesse($richesse)
    {
        $this->richesse = $richesse;

        return $this;
    }

    /**
     * Get the value of richesse.
     *
     * @return integer
     */
    public function getRichesse()
    {
        return $this->richesse;
    }

    /**
     * Set the value of heroisme.
     *
     * @param integer $heroisme
     * @return \App\Entity\Personnage
     */
    public function setHeroisme($heroisme)
    {
        $this->heroisme = $heroisme;

        return $this;
    }

    /**
     * Get the value of heroisme.
     *
     * @return integer
     */

    public function getHeroisme()
    {
        // return $this->heroisme;
        return 0;
    }

    /**
     * Set the value of pugilat.
     *
     * @param integer $pugilat
     * @return \App\Entity\Personnage
     */
    public function setPugilat($pugilat)
    {
        $this->pugilat = $pugilat;

        return $this;
    }

    /**
     * Add ExperienceGain entity to collection (one to many).
     *
     * @param \App\Entity\ExperienceGain $experienceGain
     * @return \App\Entity\Personnage
     */
    public function addExperienceGain(ExperienceGain $experienceGain)
    {
        $this->experienceGains[] = $experienceGain;

        return $this;
    }

    /**
     * Remove ExperienceGain entity from collection (one to many).
     *
     * @param \App\Entity\ExperienceGain $experienceGain
     * @return \App\Entity\Personnage
     */
    public function removeExperienceGain(ExperienceGain $experienceGain)
    {
        $this->experienceGains->removeElement($experienceGain);

        return $this;
    }

    /**
     * Get ExperienceGain entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExperienceGains()
    {
        return $this->experienceGains;
    }

    /**
     * Add ExperienceUsage entity to collection (one to many).
     *
     * @param \App\Entity\ExperienceUsage $experienceUsage
     * @return \App\Entity\Personnage
     */
    public function addExperienceUsage(ExperienceUsage $experienceUsage)
    {
        $this->experienceUsages[] = $experienceUsage;

        return $this;
    }

    /**
     * Remove ExperienceUsage entity from collection (one to many).
     *
     * @param \App\Entity\ExperienceUsage $experienceUsage
     * @return \App\Entity\Personnage
     */
    public function removeExperienceUsage(ExperienceUsage $experienceUsage)
    {
        $this->experienceUsages->removeElement($experienceUsage);

        return $this;
    }

    /**
     * Get ExperienceUsage entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExperienceUsages()
    {
        return $this->experienceUsages;
    }

    /**
     * Add HeroismeHistory entity to collection (one to many).
     *
     * @param \App\Entity\HeroismeHistory $heroismeHistory
     * @return \App\Entity\Personnage
     */
    public function addHeroismeHistory(HeroismeHistory $heroismeHistory)
    {
        $this->heroismeHistories[] = $heroismeHistory;

        return $this;
    }

    /**
     * Remove HeroismeHistory entity from collection (one to many).
     *
     * @param \App\Entity\HeroismeHistory $heroismeHistory
     * @return \App\Entity\Personnage
     */
    public function removeHeroismeHistory(HeroismeHistory $heroismeHistory)
    {
        $this->heroismeHistories->removeElement($heroismeHistory);

        return $this;
    }

    /**
     * Get HeroismeHistory entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHeroismeHistories()
    {
        return $this->heroismeHistories;
    }


    /**
     * Add PugilatHistory entity to collection (one to many).
     *
     * @param \App\Entity\PugilatHistory $pugilatHistory
     * @return \App\Entity\Personnage
     */
    public function addPugilatHistory(PugilatHistory $pugilatHistory)
    {
        $this->pugilatHistories[] = $pugilatHistory;

        return $this;
    }

    /**
     * Remove PugilatHistory entity from collection (one to many).
     *
     * @param \App\Entity\PugilatHistory $pugilatHistory
     * @return \App\Entity\Personnage
     */
    public function removePugilatHistory(PugilatHistory $pugilatHistory)
    {
        $this->pugilatHistories->removeElement($pugilatHistory);

        return $this;
    }

    /**
     * Get PugilatHistory entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPugilatHistories()
    {
        return $this->pugilatHistories;
    }

    /**
     * Add Membre entity to collection (one to many).
     *
     * @param \App\Entity\Membre $membre
     * @return \App\Entity\Personnage
     */
    public function addMembre(Membre $membre)
    {
        $this->membres[] = $membre;

        return $this;
    }

    /**
     * Remove Membre entity from collection (one to many).
     *
     * @param \App\Entity\Membre $membre
     * @return \App\Entity\Personnage
     */
    public function removeMembre(Membre $membre)
    {
        $this->membres->removeElement($membre);

        return $this;
    }

    /**
     * Get Membre entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembres()
    {
        return $this->membres;
    }

    /**
     * Add Participant entity to collection (one to many).
     *
     * @param \App\Entity\Participant $participant
     * @return \App\Entity\Personnage
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Remove Participant entity from collection (one to many).
     *
     * @param \App\Entity\Participant $participant
     * @return \App\Entity\Personnage
     */
    public function removeParticipant(Participant $participant)
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
     * Add PersonnageBackground entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageBackground $personnageBackground
     * @return \App\Entity\Personnage
     */
    public function addPersonnageBackground(PersonnageBackground $personnageBackground)
    {
        $this->personnageBackgrounds[] = $personnageBackground;

        return $this;
    }

    /**
     * Remove PersonnageBackground entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageBackground $personnageBackground
     * @return \App\Entity\Personnage
     */
    public function removePersonnageBackground(PersonnageBackground $personnageBackground)
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
     * Add PersonnageHasToken entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageHasToken $personnageHasToken
     * @return \App\Entity\Personnage
     */
    public function addPersonnageHasToken(PersonnageHasToken $personnageHasToken)
    {
        $this->personnageHasTokens[] = $personnageHasToken;

        return $this;
    }

    /**
     * Remove PersonnageHasToken entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageHasToken $personnageHasToken
     * @return \App\Entity\Personnage
     */
    public function removePersonnageHasToken(PersonnageHasToken $personnageHasToken)
    {
        $this->personnageHasTokens->removeElement($personnageHasToken);

        return $this;
    }

    /**
     * Get PersonnageHasToken entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageHasTokens()
    {
        return $this->personnageHasTokens;
    }

    /**
     * Add PersonnageIngredient entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageIngredient $personnageIngredient
     * @return \App\Entity\Personnage
     */
    public function addPersonnageIngredient(PersonnageIngredient $personnageIngredient)
    {
        $this->personnageIngredients[] = $personnageIngredient;

        return $this;
    }

    /**
     * Remove PersonnageIngredient entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageIngredient $personnageIngredient
     * @return \App\Entity\Personnage
     */
    public function removePersonnageIngredient(PersonnageIngredient $personnageIngredient)
    {
        $this->personnageIngredients->removeElement($personnageIngredient);

        return $this;
    }

    /**
     * Get PersonnageIngredient entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageIngredients()
    {
        return $this->personnageIngredients;
    }

    /**
     * Add PersonnageLangues entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageLangues $personnageLangues
     * @return \App\Entity\Personnage
     */
    public function addPersonnageLangues(PersonnageLangues $personnageLangues)
    {
        $this->personnageLangues[] = $personnageLangues;

        return $this;
    }

    /**
     * Remove PersonnageLangues entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageLangues $personnageLangues
     * @return \App\Entity\Personnage
     */
    public function removePersonnageLangues(PersonnageLangues $personnageLangues)
    {
        $this->personnageLangues->removeElement($personnageLangues);

        return $this;
    }

    /**
     * Get PersonnageLangues entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     * @OrderBy({"secret"="ASC", "diffusion"="DESC", "label"="ASC"})
     */
    public function getPersonnageLangues()
    {
        return $this->personnageLangues;
    }

    /**
     * Add PersonnageRessource entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageRessource $personnageRessource
     * @return \App\Entity\Personnage
     */
    public function addPersonnageRessource(PersonnageRessource $personnageRessource)
    {
        $this->personnageRessources[] = $personnageRessource;

        return $this;
    }

    /**
     * Remove PersonnageRessource entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageRessource $personnageRessource
     * @return \App\Entity\Personnage
     */
    public function removePersonnageRessource(PersonnageRessource $personnageRessource)
    {
        $this->personnageRessources->removeElement($personnageRessource);

        return $this;
    }

    /**
     * Get PersonnageRessource entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageRessources()
    {
        return $this->personnageRessources;
    }

    /**
     * Add PersonnageTrigger entity to collection (one to many).
     *
     * @param \App\Entity\PersonnageTrigger $personnageTrigger
     * @return \App\Entity\Personnage
     */
    public function addPersonnageTrigger(PersonnageTrigger $personnageTrigger)
    {
        $this->personnageTriggers[] = $personnageTrigger;

        return $this;
    }

    /**
     * Remove PersonnageTrigger entity from collection (one to many).
     *
     * @param \App\Entity\PersonnageTrigger $personnageTrigger
     * @return \App\Entity\Personnage
     */
    public function removePersonnageTrigger(PersonnageTrigger $personnageTrigger)
    {
        $this->personnageTriggers->removeElement($personnageTrigger);

        return $this;
    }

    /**
     * Get PersonnageTrigger entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageTriggers()
    {
        return $this->personnageTriggers;
    }

    /**
     * Add PersonnagesReligions entity to collection (one to many).
     *
     * @param \App\Entity\PersonnagesReligions $personnagesReligions
     * @return \App\Entity\Personnage
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     *
     * @param \App\Entity\PersonnagesReligions $personnagesReligions
     * @return \App\Entity\Personnage
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnagesReligions()
    {
        $iterator = $this->personnagesReligions->getIterator();
        $iterator->uasort(function (PersonnagesReligions $a, PersonnagesReligions $b) {
            return $a->getReligionLevel() <=> $b->getReligionLevel();
        });
        return new ArrayCollection(iterator_to_array($iterator));
        // return $this->personnagesReligions;
    }

    /**
     * Add Postulant entity to collection (one to many).
     *
     * @param \App\Entity\Postulant $postulant
     * @return \App\Entity\Personnage
     */
    public function addPostulant(Postulant $postulant)
    {
        $this->postulants[] = $postulant;

        return $this;
    }

    /**
     * Remove Postulant entity from collection (one to many).
     *
     * @param \App\Entity\Postulant $postulant
     * @return \App\Entity\Personnage
     */
    public function removePostulant(Postulant $postulant)
    {
        $this->postulants->removeElement($postulant);

        return $this;
    }

    /**
     * Get Postulant entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPostulants()
    {
        return $this->postulants;
    }

    /**
     * Add RenommeHistory entity to collection (one to many).
     *
     * @param \App\Entity\RenommeHistory $renommeHistory
     * @return \App\Entity\Personnage
     */
    public function addRenommeHistory(RenommeHistory $renommeHistory)
    {
        $this->renommeHistories[] = $renommeHistory;

        return $this;
    }

    /**
     * Remove RenommeHistory entity from collection (one to many).
     *
     * @param \App\Entity\RenommeHistory $renommeHistory
     * @return \App\Entity\Personnage
     */
    public function removeRenommeHistory(RenommeHistory $renommeHistory)
    {
        $this->renommeHistories->removeElement($renommeHistory);

        return $this;
    }

    /**
     * Get RenommeHistory entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRenommeHistories()
    {
        return $this->renommeHistories;
    }

    /**
     * Add SecondaryGroup entity to collection (one to many).
     *
     * @param \App\Entity\SecondaryGroup $secondaryGroup
     * @return \App\Entity\Personnage
     */
    public function addSecondaryGroup(SecondaryGroup $secondaryGroup)
    {
        $this->secondaryGroups[] = $secondaryGroup;

        return $this;
    }

    /**
     * Remove SecondaryGroup entity from collection (one to many).
     *
     * @param \App\Entity\SecondaryGroup $secondaryGroup
     * @return \App\Entity\Personnage
     */
    public function removeSecondaryGroup(SecondaryGroup $secondaryGroup)
    {
        $this->secondaryGroups->removeElement($secondaryGroup);

        return $this;
    }

    /**
     * Get SecondaryGroup entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSecondaryGroups()
    {
        return $this->secondaryGroups;
    }

    /**
     * Add User entity to collection (one to many).
     *
     * @param \App\Entity\User $User
     * @return \App\Entity\Personnage
     */
    public function addUser(User $User)
    {
        $this->Users[] = $User;

        return $this;
    }

    /**
     * Remove User entity from collection (one to many).
     *
     * @param \App\Entity\User $User
     * @return \App\Entity\Personnage
     */
    public function removeUser(User $User)
    {
        $this->Users->removeElement($User);

        return $this;
    }

    /**
     * Get User entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->Users;
    }

    /**
     * Set Groupe entity (many to one).
     *
     * @param \App\Entity\Groupe $groupe
     * @return \App\Entity\Personnage
     */
    public function setGroupe(Groupe $groupe = null)
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     *
     * @return \App\Entity\Groupe
     */
    public function getGroupe()
    {
        return $this->groupe;
    }

    /**
     * Set Classe entity (many to one).
     *
     * @param \App\Entity\Classe $classe
     * @return \App\Entity\Personnage
     */
    public function setClasse(Classe $classe = null)
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * Get Classe entity (many to one).
     *
     * @return \App\Entity\Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    /**
     * Set Age entity (many to one).
     *
     * @param \App\Entity\Age $age
     * @return \App\Entity\Personnage
     */
    public function setAge(Age $age = null)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get Age entity (many to one).
     *
     * @return \App\Entity\Age
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set Genre entity (many to one).
     *
     * @param \App\Entity\Genre $genre
     * @return \App\Entity\Personnage
     */
    public function setGenre(Genre $genre = null)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get Genre entity (many to one).
     *
     * @return \App\Entity\Genre
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set Territoire entity (many to one).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Personnage
     */
    public function setTerritoire(Territoire $territoire = null)
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (many to one).
     *
     * @return \App\Entity\Territoire
     */
    public function getTerritoire()
    {
        return $this->territoire;
    }

    /**
     * Set User entity (many to one).
     *
     * @param \App\Entity\User $User
     * @return \App\Entity\Personnage
     */
    public function setUser(User $User = null)
    {
        $this->User = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Add Document entity to collection.
     *
     * @param \App\Entity\Document $document
     * @return \App\Entity\Personnage
     */
    public function addDocument(Document $document)
    {
        $document->addPersonnage($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove Document entity from collection.
     *
     * @param \App\Entity\Document $document
     * @return \App\Entity\Personnage
     */
    public function removeDocument(Document $document)
    {
        $document->removePersonnage($this);
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Get Document entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Add Item entity to collection.
     *
     * @param \App\Entity\Item $item
     * @return \App\Entity\Personnage
     */
    public function addItem(Item $item)
    {
        $item->addPersonnage($this);
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection.
     *
     * @param \App\Entity\Item $item
     * @return \App\Entity\Personnage
     */
    public function removeItem(Item $item)
    {
        $item->removePersonnage($this);
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add Technologie entity to collection.
     *
     * @param \App\Entity\Technologie $technologie
     * @return \App\Entity\Personnage
     */
    public function addTechnologie(Technologie $technologie)
    {
        $technologie->addPersonnage($this);
        $this->technologies[] = $technologie;

        return $this;
    }

    /**
     * Remove Technologie entity from collection.
     *
     * @param \App\Entity\Technologie $technologie
     * @return \App\Entity\Personnage
     */
    public function removeTechnologie(Technologie $technologie)
    {
        $technologie->removePersonnage($this);
        $this->technologies->removeElement($technologie);

        return $this;
    }

    /**
     * Get Technologie entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTechnologies()
    {
        return $this->technologies;
    }

    /**
     * Add Religion entity to collection.
     *
     * @param \App\Entity\Religion $religion
     * @return \App\Entity\Personnage
     */
    public function addReligion(Religion $religion)
    {
        $religion->addPersonnage($this);
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Remove Religion entity from collection.
     *
     * @param \App\Entity\Religion $religion
     * @return \App\Entity\Personnage
     */
    public function removeReligion(Religion $religion)
    {
        $religion->removePersonnage($this);
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Get Religion entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReligions()
    {
        return $this->religions;
    }

    /**
     * Add Competence entity to collection.
     *
     * @param \App\Entity\Competence $competence
     * @return \App\Entity\Personnage
     */
    public function addCompetence(Competence $competence)
    {
        $this->competences[] = $competence;

        return $this;
    }

    /**
     * Remove Competence entity from collection.
     *
     * @param \App\Entity\Competence $competence
     * @return \App\Entity\Personnage
     */
    public function removeCompetence(Competence $competence)
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    /**
     * Get Competence entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCompetences()
    {
        $iterator = $this->competences->getIterator();
        $iterator->uasort(function (Competence $a, Competence $b) {
            return $a->getCompetenceFamily()->getLabel() <=> $b->getCompetenceFamily()->getLabel();
        });
        return new ArrayCollection(iterator_to_array($iterator));
        // return $this->competences;
    }

    /**
     * Add Domaine entity to collection.
     *
     * @param \App\Entity\Domaine $domaine
     * @return \App\Entity\Personnage
     */
    public function addDomaine(Domaine $domaine)
    {
        $domaine->addPersonnage($this);
        $this->domaines[] = $domaine;

        return $this;
    }

    /**
     * Remove Domaine entity from collection.
     *
     * @param \App\Entity\Domaine $domaine
     * @return \App\Entity\Personnage
     */
    public function removeDomaine(Domaine $domaine)
    {
        $domaine->removePersonnage($this);
        $this->domaines->removeElement($domaine);

        return $this;
    }

    /**
     * Get Domaine entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDomaines()
    {
        return $this->domaines;
    }

    /**
     * Add Potion entity to collection.
     *
     * @param \App\Entity\Potion $potion
     * @return \App\Entity\Personnage
     */
    public function addPotion(Potion $potion)
    {
        $potion->addPersonnage($this);
        $this->potions[] = $potion;

        return $this;
    }

    /**
     * Remove Potion entity from collection.
     *
     * @param \App\Entity\Potion $potion
     * @return \App\Entity\Personnage
     */
    public function removePotion(Potion $potion)
    {
        $potion->removePersonnage($this);
        $this->potions->removeElement($potion);

        return $this;
    }

    /**
     * Get Potion entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPotions()
    {
        return $this->potions;
    }

    /**
     * Add Priere entity to collection.
     *
     * @param \App\Entity\Priere $priere
     * @return \App\Entity\Personnage
     */
    public function addPriere(Priere $priere)
    {
        $this->prieres[] = $priere;

        return $this;
    }

    /**
     * Remove Priere entity from collection.
     *
     * @param \App\Entity\Priere $priere
     * @return \App\Entity\Personnage
     */
    public function removePriere(Priere $priere)
    {
        $this->prieres->removeElement($priere);

        return $this;
    }

    /**
     * Get Priere entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrieres()
    {
        return $this->prieres;
    }

    /**
     * Add Sort entity to collection.
     *
     * @param \App\Entity\Sort $sort
     * @return \App\Entity\Personnage
     */
    public function addSort(Sort $sort)
    {
        $sort->addPersonnage($this);
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * Remove Sort entity from collection.
     *
     * @param \App\Entity\Sort $sort
     * @return \App\Entity\Personnage
     */
    public function removeSort(Sort $sort)
    {
        $sort->removePersonnage($this);
        $this->sorts->removeElement($sort);

        return $this;
    }

    /**
     * Get Sort entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * Add Connaissance entity to collection.
     *
     * @param \App\Entity\Connaissance $connaissance
     * @return \App\Entity\Personnage
     */
    public function addConnaissance(Connaissance $connaissance)
    {
        $connaissance->addPersonnage($this);
        $this->connaissances[] = $connaissance;

        return $this;
    }

    /**
     * Remove Connaissance entity from collection.
     *
     * @param \App\Entity\Connaissance $connaissance
     * @return \App\Entity\Personnage
     */
    public function removeConnaissance(Connaissance $connaissance)
    {
        $connaissance->removePersonnage($this);
        $this->connaissances->removeElement($connaissance);

        return $this;
    }

    /**
     * Get Connaissance entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConnaissances()
    {
        return $this->connaissances;
    }

    /**
     * Get personnageChronologie entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageChronologie()
    {
        return $this->personnageChronologie;
    }

    /**
     * Get personnageLignee entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageLignee()
    {
        return $this->personnageLignee;
    }

    public function __sleep()
    {
        return array('id', 'nom', 'surnom', 'intrigue', 'groupe_id', 'classe_id', 'age_id', 'genre_id', 'renomme', 'photo', 'xp', 'territoire_id', 'materiel', 'vivant', 'age_reel', 'trombineUrl', 'User_id', 'richesse', 'heroisme');
    }
    
    /**
     * @return la prochaine partipcipation
     */
    public function prochaineParticipation()
    {
        $now = new \DateTime();
        $prochain = null;
        
        foreach ($this->participants as $p) {
            if($now < $p->getGn()->getDateFin()) {
                if($prochain == null || $prochain->getGn()->getDateDebut() > $p->getGn()->getDateDebut() ) {
                    $prochain = $p;
                }
            }
        }
        return $prochain;
    }

}
