<?php

/**
 * ATTENTION Cette classe n'est pas généré car elle contient une ManyToMany sur elle-même
 * à travers la table territoire_quete.
 * Ce type de relation n'est pas modélisable dans MySQLWorkbench sans passer par une table nommé.
 * Mais elle est modélisable via Doctrine.
 * Donc si vous modifiez cette classe. Veuillez veiller à ne pas écraser territoireCibles & territoireStarts.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'territoire')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_territoire_territoire1_idx')]
#[ORM\Index(columns: ['territoire_guerre_id'], name: 'fk_territoire_territoire_guerre1_idx')]
#[ORM\Index(columns: ['appelation_id'], name: 'fk_territoire_appelation1_idx')]
#[ORM\Index(columns: ['langue_id'], name: 'fk_territoire_langue1_idx')]
#[ORM\Index(columns: ['religion_id'], name: 'fk_territoire_religion1_idx')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_territoire_groupe1_idx')]
#[ORM\Index(columns: ['culture_id'], name: 'fk_territoire_culture1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTerritoire', 'extended' => 'Territoire'])]
abstract class BaseTerritoire
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected string $nom = '';

    #[Column(type: Types::STRING, length: 180, unique: true)]
    protected ?string $description = null;

    #[Column(type: Types::STRING, length: 180, unique: true)]
    protected ?string $capitale = null;

    #[Column(type: Types::STRING, length: 180, unique: true)]
    protected ?string $politique = null;

    #[Column(type: Types::STRING, length: 180, unique: true)]
    protected ?string $dirigeant = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $population = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $symbole = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $tech_level = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $type_racial = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $inspiration = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $armes_predilection = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $vetements = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $noms_masculin = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $noms_feminin = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $frontieres = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $geojson = null;

    #[Column(type: Types::STRING, length: 7, nullable: true)]
    protected ?string $color = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tresor = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    protected ?int $resistance = 0;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $blason = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $description_secrete = null;

    #[Column(type: Types::TEXT, length: 45, nullable: true)]
    protected ?string $statut = null;

    #[Column(type: Types::INTEGER)]
    protected int $ordre_social = 0;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Chronologie::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'zone_politique_id', nullable: 'false')]
    protected Collection $chronologies;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Groupe::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'territoire_id', nullable: 'false')]
    protected ?Collection $groupes;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Personnage::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'territoire_id', nullable: 'false')]
    protected Collection $personnages;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Rumeur::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'territoire_id', nullable: 'false')]
    protected Collection $rumeurs;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Territoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'territoire_id', nullable: 'false')]
    protected Collection $territoires;

    #[OneToMany(mappedBy: 'territoire', targetEntity: TitreTerritoire::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'territoire_id', nullable: 'false')]
    protected Collection $titreTerritoires;

    #[OneToMany(mappedBy: 'cible', targetEntity: GroupeGnOrdre::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'cible_id', nullable: 'false')]
    protected Collection $groupeGnOrdres;

    #[ORM\ManyToOne(targetEntity: Territoire::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id')]
    protected ?Territoire $territoire;

    #[ORM\OneToOne(mappedBy: 'territoire', targetEntity: TerritoireGuerre::class)]
    #[JoinColumn(name: 'territoire_guerre_id', referencedColumnName: 'id')]
    protected TerritoireGuerre $territoireGuerre;

    #[ORM\ManyToOne(targetEntity: Appelation::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'appelation_id', referencedColumnName: 'id')]
    protected Appelation $appelation;

    #[ORM\ManyToOne(targetEntity: Langue::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'langue_id', referencedColumnName: 'id')]
    protected ?Langue $langue;

    #[ORM\ManyToOne(targetEntity: Religion::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'religion_id', referencedColumnName: 'id')]
    protected ?Religion $religion;

    #[ORM\ManyToMany(targetEntity: Territoire::class, inversedBy: 'territoireStarts')]
    #[ORM\JoinTable(name: 'territoire_quete')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'territoire_cible_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $territoireCibles;

    #[ORM\ManyToMany(targetEntity: Territoire::class, mappedBy: 'territoireCibles')]
    protected Collection $territoireStarts;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id')]
    protected ?Groupe $groupe = null;

    #[ORM\ManyToOne(targetEntity: Culture::class, inversedBy: 'territoires')]
    #[JoinColumn(name: 'culture_id', referencedColumnName: 'id')]
    protected ?Culture $culture = null;

    #[ORM\ManyToMany(targetEntity: Construction::class, inversedBy: 'territoires')]
    #[ORM\JoinTable(name: 'territoire_has_construction')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'construction_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $constructions;

    #[ORM\ManyToMany(targetEntity: Loi::class, inversedBy: 'territoires')]
    #[ORM\JoinTable(name: 'territoire_has_loi')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'loi_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $lois;

    #[ORM\ManyToMany(targetEntity: Ingredient::class, inversedBy: 'territoires')]
    #[ORM\JoinTable(name: 'territoire_ingredient')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'ingredient_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $ingredients;

    #[ORM\ManyToMany(targetEntity: Ressource::class, inversedBy: 'importateurs')]
    #[ORM\JoinTable(name: 'territoire_importation')]
    #[JoinColumn(name: 'ressource_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'ressource_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $importations;

    #[ORM\ManyToMany(targetEntity: Ressource::class, inversedBy: 'exportateurs')]
    #[ORM\JoinTable(name: 'territoire_exportation')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'ressource_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $exportations;

    #[ORM\ManyToMany(targetEntity: Langue::class, inversedBy: 'territoireSecondaires')]
    #[ORM\JoinTable(name: 'territoire_langue')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'langue_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $langues;

    #[ORM\ManyToMany(targetEntity: Religion::class, inversedBy: 'territoireSecondaires')]
    #[ORM\JoinTable(name: 'territoire_religion')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'religion_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $religions;

    /**
     * @var Collection<int, OrigineBonus>|null
     */
    #[OneToMany(mappedBy: 'territoire', targetEntity: OrigineBonus::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'origine_bonus')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: 'false')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private ?Collection $originesBonus;

    #[OneToMany(mappedBy: 'territoire', targetEntity: Merveille::class)]
    private Collection $merveilles;

    public function __construct()
    {
        $this->chronologies = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->rumeurs = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->titreTerritoires = new ArrayCollection();
        $this->constructions = new ArrayCollection();
        $this->lois = new ArrayCollection();
        $this->ingredients = new ArrayCollection();
        $this->territoireCibles = new ArrayCollection();
        $this->territoireStarts = new ArrayCollection();
        $this->importations = new ArrayCollection();
        $this->exportations = new ArrayCollection();
        $this->langues = new ArrayCollection();
        $this->religions = new ArrayCollection();
        $this->originesBonus = new ArrayCollection();
        $this->merveilles = new ArrayCollection();
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
        return $this->nom ?? '';
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
     * Set the value of capitale.
     */
    public function setCapitale(string $capitale): static
    {
        $this->capitale = $capitale;

        return $this;
    }

    /**
     * Get the value of capitale.
     */
    public function getCapitale(): string
    {
        return $this->capitale ?? '';
    }

    /**
     * Set the value of politique.
     */
    public function setPolitique(?string $politique): static
    {
        $this->politique = $politique;

        return $this;
    }

    /**
     * Get the value of politique.
     */
    public function getPolitique(): string
    {
        return $this->politique ?? '';
    }

    /**
     * Set the value of dirigeant.
     */
    public function setDirigeant(?string $dirigeant): static
    {
        $this->dirigeant = $dirigeant;

        return $this;
    }

    /**
     * Get the value of dirigeant.
     */
    public function getDirigeant(): string
    {
        return $this->dirigeant ?? '';
    }

    /**
     * Set the value of population.
     */
    public function setPopulation(?string $population)
    {
        $this->population = $population;

        return $this;
    }

    /**
     * Get the value of population.
     */
    public function getPopulation(): string
    {
        return $this->population ?? '';
    }

    /**
     * Set the value of symbole.
     */
    public function setSymbole(?string $symbole): static
    {
        $this->symbole = $symbole;

        return $this;
    }

    /**
     * Get the value of symbole.
     */
    public function getSymbole(): string
    {
        return $this->symbole ?? '';
    }

    /**
     * Set the value of tech_level.
     */
    public function setTechLevel(?string $tech_level): static
    {
        $this->tech_level = $tech_level;

        return $this;
    }

    /**
     * Get the value of tech_level.
     */
    public function getTechLevel(): string
    {
        return $this->tech_level ?? '';
    }

    /**
     * Set the value of type_racial.
     */
    public function setTypeRacial(?string $type_racial): static
    {
        $this->type_racial = $type_racial;

        return $this;
    }

    /**
     * Get the value of type_racial.
     */
    public function getTypeRacial(): string
    {
        return $this->type_racial ?? '';
    }

    /**
     * Set the value of inspiration.
     */
    public function setInspiration(?string $inspiration): static
    {
        $this->inspiration = $inspiration;

        return $this;
    }

    /**
     * Get the value of inspiration.
     */
    public function getInspiration(): string
    {
        return $this->inspiration ?? '';
    }

    /**
     * Set the value of armes_predilection.
     */
    public function setArmesPredilection(?string $armes_predilection): static
    {
        $this->armes_predilection = $armes_predilection;

        return $this;
    }

    /**
     * Get the value of armes_predilection.
     */
    public function getArmesPredilection(): string
    {
        return $this->armes_predilection ?? '';
    }

    /**
     * Set the value of vetements.
     */
    public function setVetements(?string $vetements): static
    {
        $this->vetements = $vetements;

        return $this;
    }

    /**
     * Get the value of vetements.
     */
    public function getVetements(): string
    {
        return $this->vetements ?? '';
    }

    /**
     * Set the value of noms_masculin.
     */
    public function setNomsMasculin(?string $noms_masculin): static
    {
        $this->noms_masculin = $noms_masculin;

        return $this;
    }

    /**
     * Get the value of noms_masculin.
     */
    public function getNomsMasculin(): string
    {
        return $this->noms_masculin ?? '';
    }

    /**
     * Set the value of noms_feminin.
     */
    public function setNomsFeminin(?string $noms_feminin): static
    {
        $this->noms_feminin = $noms_feminin;

        return $this;
    }

    /**
     * Get the value of noms_feminin.
     */
    public function getNomsFeminin(): string
    {
        return $this->noms_feminin ?? '';
    }

    /**
     * Set the value of frontieres.
     */
    public function setFrontieres(?string $frontieres): static
    {
        $this->frontieres = $frontieres;

        return $this;
    }

    /**
     * Get the value of frontieres.
     */
    public function getFrontieres(): string
    {
        return $this->frontieres ?? '';
    }

    /**
     * Set the value of geojson.
     */
    public function setGeojson(?string $geojson): static
    {
        $this->geojson = $geojson;

        return $this;
    }

    /**
     * Get the value of geojson.
     */
    public function getGeojson(): ?string
    {
        return $this->geojson;
    }

    /**
     * Set the value of color.
     */
    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get the value of color.
     */
    public function getColor(): string
    {
        return $this->color ?? '';
    }

    /**
     * Set the value of tresor.
     *
     * @param int $tresor
     */
    public function setTresor($tresor): static
    {
        $this->tresor = $tresor;

        return $this;
    }

    /**
     * Get the value of tresor.
     */
    public function getTresor(): ?int
    {
        return $this->tresor;
    }

    /**
     * Set the value of resistance.
     *
     * @param int $resistance
     */
    public function setResistance(string $resistance): static
    {
        $this->resistance = $resistance;

        return $this;
    }

    /**
     * Get the value of resistance.
     */
    public function getResistance(): ?int
    {
        return $this->resistance;
    }

    /**
     * Set the value of blason.
     */
    public function setBlason(string $blason): static
    {
        $this->blason = $blason;

        return $this;
    }

    /**
     * Get the value of blason.
     */
    public function getBlason(): string
    {
        return $this->blason ?? '';
    }

    /**
     * Set the value of description_secrete.
     */
    public function setDescriptionSecrete(?string $description_secrete): static
    {
        $this->description_secrete = $description_secrete;

        return $this;
    }

    /**
     * Get the value of description_secrete.
     */
    public function getDescriptionSecrete(): string
    {
        return $this->description_secrete ?? '';
    }

    /**
     * Set the value of statut.
     */
    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get the value of statut.
     */
    public function getStatut(): string
    {
        return $this->statut ?? '';
    }

    /**
     * Set the value of ordre_social.
     *
     * @param int $ordre_social
     */
    public function setOrdreSocial(string $ordre_social): static
    {
        $this->ordre_social = $ordre_social;

        return $this;
    }

    /**
     * Get the value of ordre_social.
     */
    public function getOrdreSocial(): int
    {
        return $this->ordre_social;
    }

    /**
     * Add Chronologie entity to collection (one to many).
     */
    public function addChronologie(Chronologie $chronologie): static
    {
        $this->chronologies[] = $chronologie;

        return $this;
    }

    /**
     * Remove Chronologie entity from collection (one to many).
     */
    public function removeChronologie(Chronologie $chronologie): static
    {
        $this->chronologies->removeElement($chronologie);

        return $this;
    }

    /**
     * Get Chronologie entity collection (one to many).
     */
    public function getChronologies(): Collection
    {
        return $this->chronologies;
    }

    /**
     * Add Territoire entity to collection (many to many).
     */
    public function addTerritoireCible(Territoire $territoire): static
    {
        $this->territoireCibles[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (many to many).
     */
    public function removeTerritoireCible(Territoire $territoire): static
    {
        $this->territoireCibles->removeElement($territoire);

        return $this;
    }

    /**
     * Get territoireCibles entity collection.
     */
    public function getTerritoireCibles(): Collection
    {
        return $this->territoireCibles;
    }

    /**
     * Add Territoire entity to collection (many to many).
     */
    public function addTerritoireStart(Territoire $territoire): static
    {
        $this->territoireStarts[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (many to many).
     */
    public function removeTerritoireStart(Territoire $territoire): static
    {
        $this->territoireStarts->removeElement($territoire);

        return $this;
    }

    /**
     * Get TerritoireStarts entity collection.
     */
    public function getTerritoireStarts(): Collection
    {
        return $this->territoireStarts;
    }

    /**
     * Add Groupe entity to collection (one to many).
     */
    public function addGroupe(Groupe $groupe): static
    {
        $this->groupes[] = $groupe;

        return $this;
    }

    /**
     * Remove Groupe entity from collection (one to many).
     */
    public function removeGroupe(Groupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity collection (one to many).
     */
    public function getGroupes(): ?Collection
    {
        return $this->groupes;
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
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    /**
     * Add Rumeur entity to collection (one to many).
     */
    public function addRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs[] = $rumeur;

        return $this;
    }

    /**
     * Remove Rumeur entity from collection (one to many).
     */
    public function removeRumeur(Rumeur $rumeur): static
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    /**
     * Get Rumeur entity collection (one to many).
     */
    public function getRumeurs(): Collection
    {
        return $this->rumeurs;
    }

    /**
     * Add Territoire entity to collection (one to many).
     */
    public function addTerritoire(Territoire $territoire): static
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     */
    public function removeTerritoire(Territoire $territoire): static
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     */
    public function getTerritoires(): Collection
    {
        return $this->territoires;
    }

    /**
     * Add TitreTerritoire entity to collection (one to many).
     */
    public function addTitreTerritoire(TitreTerritoire $titreTerritoire): static
    {
        $this->titreTerritoires[] = $titreTerritoire;

        return $this;
    }

    /**
     * Remove TitreTerritoire entity from collection (one to many).
     */
    public function removeTitreTerritoire(TitreTerritoire $titreTerritoire): static
    {
        $this->titreTerritoires->removeElement($titreTerritoire);

        return $this;
    }

    /**
     * Get TitreTerritoire entity collection (one to many).
     */
    public function getTitreTerritoires(): Collection
    {
        return $this->titreTerritoires;
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
     * Get Territoire entity (many to one).
     */
    public function getTerritoire(): ?Territoire
    {
        return $this->territoire;
    }

    /**
     * Set TerritoireGuerre entity (one to one).
     */
    public function setTerritoireGuerre(TerritoireGuerre $territoireGuerre): static
    {
        $this->territoireGuerre = $territoireGuerre;

        return $this;
    }

    /**
     * Get TerritoireGuerre entity (one to one).
     */
    public function getTerritoireGuerre(): TerritoireGuerre
    {
        return $this->territoireGuerre;
    }

    /**
     * Set Appelation entity (many to one).
     */
    public function setAppelation(?Appelation $appelation = null): static
    {
        $this->appelation = $appelation;

        return $this;
    }

    /**
     * Get Appelation entity (many to one).
     */
    public function getAppelation(): Appelation
    {
        return $this->appelation;
    }

    /**
     * Set Langue entity (many to one).
     */
    public function setLangue(?Langue $langue = null): static
    {
        $this->langue = $langue;

        return $this;
    }

    /**
     * Get Langue entity (many to one).
     */
    public function getLangue(): ?Langue
    {
        return $this->langue;
    }

    /**
     * Set Religion entity (many to one).
     */
    public function setReligion(?Religion $religion = null): static
    {
        $this->religion = $religion;

        return $this;
    }

    /**
     * Get Religion entity (many to one).
     */
    public function getReligion(): ?Religion
    {
        return $this->religion;
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
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    /**
     * Set Culture entity (many to one).
     */
    public function setCulture(?Culture $culture = null): static
    {
        $this->culture = $culture;

        return $this;
    }

    /**
     * Get Culture entity (many to one).
     */
    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    /**
     * Add Construction entity to collection.
     */
    public function addConstruction(Construction $construction): static
    {
        $construction->addTerritoire($this);
        $this->constructions[] = $construction;

        return $this;
    }

    /**
     * Remove Construction entity from collection.
     */
    public function removeConstruction(Construction $construction): static
    {
        $construction->removeTerritoire($this);
        $this->constructions->removeElement($construction);

        return $this;
    }

    /**
     * Get Construction entity collection.
     */
    public function getConstructions(): Collection
    {
        return $this->constructions;
    }

    /**
     * Add Loi entity to collection.
     */
    public function addLoi(Loi $loi): static
    {
        $loi->addTerritoire($this);
        $this->lois[] = $loi;

        return $this;
    }

    /**
     * Remove Loi entity from collection.
     */
    public function removeLoi(Loi $loi): static
    {
        $loi->removeTerritoire($this);
        $this->lois->removeElement($loi);

        return $this;
    }

    /**
     * Get Loi entity collection.
     */
    public function getLois(): Collection
    {
        return $this->lois;
    }

    /**
     * Add Ingredient entity to collection.
     */
    public function addIngredient(Ingredient $ingredient): static
    {
        $ingredient->addTerritoire($this);
        $this->ingredients[] = $ingredient;

        return $this;
    }

    /**
     * Remove Ingredient entity from collection.
     */
    public function removeIngredient(Ingredient $ingredient): static
    {
        $ingredient->removeTerritoire($this);
        $this->ingredients->removeElement($ingredient);

        return $this;
    }

    /**
     * Get Ingredient entity collection.
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    /**
     * Add Langue entity to collection.
     */
    public function addLangue(Langue $langue): static
    {
        $langue->addTerritoireSecondaire($this);
        $this->langues[] = $langue;

        return $this;
    }

    /**
     * Remove Langue entity from collection.
     */
    public function removeLangue(Langue $langue): static
    {
        $langue->removeTerritoireSecondaire($this);
        $this->langues->removeElement($langue);

        return $this;
    }

    /**
     * Get Langue entity collection.
     */
    public function getLangues(): Collection
    {
        return $this->langues;
    }

    /**
     * Ajoute une religion dans la collection de religion.
     */
    public function addReligion(Religion $religion): static
    {
        $religion->addTerritoireSecondaire($this);
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Retire une religion de la collection de religion.
     */
    public function removeReligion(Religion $religion): static
    {
        $religion->removeTerritoireSecondaire($this);
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Fourni la collection de religions.
     */
    public function getReligions(): Collection
    {
        return $this->religions;
    }

    public function setGroupeNull(): static
    {
        $this->groupe = null;

        return $this;
    }

    public function getOriginesBonus(): ?Collection
    {
        return $this->originesBonus;
    }

    public function addOrigineBonus(OrigineBonus $origineBonus): static
    {
        if (!$this->originesBonus->contains($origineBonus)) {
            $this->originesBonus->add($origineBonus);
            $origineBonus->setTerritoire($this);
        }

        return $this;
    }

    public function removeOrigineBonus(OrigineBonus $origineBonus): static
    {
        if ($this->originesBonus->removeElement($origineBonus)) {
            // set the owning side to null (unless already changed)
            if ($origineBonus->getTerritoire() === $this) {
                $origineBonus->setTerritoire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Merveille>
     */
    public function getMerveilles(): Collection
    {
        return $this->merveilles;
    }

    public function addMerveille(Merveille $merveille): static
    {
        if (!$this->merveilles->contains($merveille)) {
            $this->merveilles->add($merveille);
            $merveille->setTerritoire($this);
        }

        return $this;
    }

    public function removeMerveille(Merveille $merveille): static
    {
        if ($this->merveilles->removeElement($merveille)) {
            // set the owning side to null (unless already changed)
            if ($merveille->getTerritoire() === $this) {
                $merveille->setTerritoire(null);
            }
        }

        return $this;
    }
}
