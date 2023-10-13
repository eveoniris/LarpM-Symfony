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

/**
 * App\Entity\Territoire
 *
 * @Table(name="territoire", indexes={@Index(name="fk_zone_politique_zone_politique1_idx", columns={"territoire_id"}), @Index(name="fk_territoire_territoire_guerre1_idx", columns={"territoire_guerre_id"}), @Index(name="fk_territoire_appelation1_idx", columns={"appelation_id"}), @Index(name="fk_territoire_langue1_idx", columns={"langue_id"}), @Index(name="fk_territoire_topic1_idx", columns={"topic_id"}), @Index(name="fk_territoire_religion1_idx", columns={"religion_id"}), @Index(name="fk_territoire_groupe1_idx", columns={"groupe_id"}), @Index(name="fk_territoire_culture1_idx", columns={"culture_id"})})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BaseTerritoire", "extended":"Territoire"})
 */
class BaseTerritoire
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=45)
     */
    protected $nom;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $capitale;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $politique;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $dirigeant;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $population;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $symbole;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $tech_level;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $type_racial;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $inspiration;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $armes_predilection;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $vetements;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $noms_masculin;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $noms_feminin;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $frontieres;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $geojson;

    /**
     * @Column(type="string", length=7, nullable=true)
     */
    protected $color;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $tresor;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $resistance;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $blason;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_secrete;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $statut;

    /**
     * @Column(type="integer")
     */
    protected $ordre_social;

    /**
     * @OneToMany(targetEntity="Chronologie", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="zone_politique_id", nullable=false)
     */
    protected $chronologies;

    /**
     * @OneToMany(targetEntity="Groupe", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="territoire_id", nullable=false)
     */
    protected $groupes;

    /**
     * @OneToMany(targetEntity="Personnage", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="territoire_id", nullable=false)
     */
    protected $personnages;

    /**
     * @OneToMany(targetEntity="Rumeur", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="territoire_id", nullable=false)
     */
    protected $rumeurs;

    /**
     * @OneToMany(targetEntity="Territoire", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="territoire_id", nullable=false)
     */
    protected $territoires;

    /**
     * @OneToMany(targetEntity="TitreTerritoire", mappedBy="territoire")
     * @JoinColumn(name="id", referencedColumnName="territoire_id", nullable=false)
     */
    protected $titreTerritoires;

    /**
     * @ManyToOne(targetEntity="Territoire", inversedBy="territoires")
     * @JoinColumn(name="territoire_id", referencedColumnName="id")
     */
    protected $territoire;

    /**
     * @OneToOne(targetEntity="TerritoireGuerre", inversedBy="territoire")
     * @JoinColumn(name="territoire_guerre_id", referencedColumnName="id")
     */
    protected $territoireGuerre;

    /**
     * @ManyToOne(targetEntity="Appelation", inversedBy="territoires")
     * @JoinColumn(name="appelation_id", referencedColumnName="id", nullable=false)
     */
    protected $appelation;

    /**
     * @ManyToOne(targetEntity="Langue", inversedBy="territoires")
     * @JoinColumn(name="langue_id", referencedColumnName="id")
     */
    protected $langue;

    /**
     * @ManyToOne(targetEntity="Topic", inversedBy="territoires")
     * @JoinColumn(name="topic_id", referencedColumnName="id", nullable=false)
     */
    protected $topic;

    /**
     * @ManyToOne(targetEntity="Religion", inversedBy="territoires")
     * @JoinColumn(name="religion_id", referencedColumnName="id")
     */
    protected $religion;

    /**
     * @ManyToMany(targetEntity="Territoire", inversedBy="territoireStarts")
     * @JoinTable(name="territoire_quete",
     *     joinColumns={@JoinColumn(name="territoire_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="territoire_cible_id", referencedColumnName="id", nullable=false)}
     * )
     */
    protected $territoireCibles;

    /**
     * @ManyToMany(targetEntity="Territoire", mappedBy="territoireCibles")
     */
    protected $territoireStarts;

    /**
     * @ManyToOne(targetEntity="Groupe", inversedBy="territoires")
     * @JoinColumn(name="groupe_id", referencedColumnName="id")
     */
    protected $groupe;

    /**
     * @ManyToOne(targetEntity="Culture", inversedBy="territoires")
     * @JoinColumn(name="culture_id", referencedColumnName="id")
     */
    protected $culture;

    /**
     * @ManyToMany(targetEntity="Construction", inversedBy="territoires")
     * @JoinTable(name="territoire_has_construction",
     *     joinColumns={@JoinColumn(name="territoire_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="construction_id", referencedColumnName="id", nullable=false)}
     * )
     * @OrderBy({"label" = "ASC",})
     */
    protected $constructions;

    /**
     * @ManyToMany(targetEntity="Loi", inversedBy="territoires")
     * @JoinTable(name="territoire_has_loi",
     *     joinColumns={@JoinColumn(name="territoire_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="loi_id", referencedColumnName="id", nullable=false)}
     * )
     */
    protected $lois;

    /**
     * @ManyToMany(targetEntity="Ingredient", inversedBy="territoires")
     * @JoinTable(name="territoire_ingredient",
     *     joinColumns={@JoinColumn(name="territoire_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="ingredient_id", referencedColumnName="id", nullable=false)}
     * )
     */
    protected $ingredients;

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
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\Territoire
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
     * @return \App\Entity\Territoire
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
     * Set the value of description.
     *
     * @param string $description
     * @return \App\Entity\Territoire
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of capitale.
     *
     * @param string $capitale
     * @return \App\Entity\Territoire
     */
    public function setCapitale($capitale)
    {
        $this->capitale = $capitale;

        return $this;
    }

    /**
     * Get the value of capitale.
     *
     * @return string
     */
    public function getCapitale()
    {
        return $this->capitale;
    }

    /**
     * Set the value of politique.
     *
     * @param string $politique
     * @return \App\Entity\Territoire
     */
    public function setPolitique($politique)
    {
        $this->politique = $politique;

        return $this;
    }

    /**
     * Get the value of politique.
     *
     * @return string
     */
    public function getPolitique()
    {
        return $this->politique;
    }

    /**
     * Set the value of dirigeant.
     *
     * @param string $dirigeant
     * @return \App\Entity\Territoire
     */
    public function setDirigeant($dirigeant)
    {
        $this->dirigeant = $dirigeant;

        return $this;
    }

    /**
     * Get the value of dirigeant.
     *
     * @return string
     */
    public function getDirigeant()
    {
        return $this->dirigeant;
    }

    /**
     * Set the value of population.
     *
     * @param string $population
     * @return \App\Entity\Territoire
     */
    public function setPopulation($population)
    {
        $this->population = $population;

        return $this;
    }

    /**
     * Get the value of population.
     *
     * @return string
     */
    public function getPopulation()
    {
        return $this->population;
    }

    /**
     * Set the value of symbole.
     *
     * @param string $symbole
     * @return \App\Entity\Territoire
     */
    public function setSymbole($symbole)
    {
        $this->symbole = $symbole;

        return $this;
    }

    /**
     * Get the value of symbole.
     *
     * @return string
     */
    public function getSymbole()
    {
        return $this->symbole;
    }

    /**
     * Set the value of tech_level.
     *
     * @param string $tech_level
     * @return \App\Entity\Territoire
     */
    public function setTechLevel($tech_level)
    {
        $this->tech_level = $tech_level;

        return $this;
    }

    /**
     * Get the value of tech_level.
     *
     * @return string
     */
    public function getTechLevel()
    {
        return $this->tech_level;
    }

    /**
     * Set the value of type_racial.
     *
     * @param string $type_racial
     * @return \App\Entity\Territoire
     */
    public function setTypeRacial($type_racial)
    {
        $this->type_racial = $type_racial;

        return $this;
    }

    /**
     * Get the value of type_racial.
     *
     * @return string
     */
    public function getTypeRacial()
    {
        return $this->type_racial;
    }

    /**
     * Set the value of inspiration.
     *
     * @param string $inspiration
     * @return \App\Entity\Territoire
     */
    public function setInspiration($inspiration)
    {
        $this->inspiration = $inspiration;

        return $this;
    }

    /**
     * Get the value of inspiration.
     *
     * @return string
     */
    public function getInspiration()
    {
        return $this->inspiration;
    }

    /**
     * Set the value of armes_predilection.
     *
     * @param string $armes_predilection
     * @return \App\Entity\Territoire
     */
    public function setArmesPredilection($armes_predilection)
    {
        $this->armes_predilection = $armes_predilection;

        return $this;
    }

    /**
     * Get the value of armes_predilection.
     *
     * @return string
     */
    public function getArmesPredilection()
    {
        return $this->armes_predilection;
    }

    /**
     * Set the value of vetements.
     *
     * @param string $vetements
     * @return \App\Entity\Territoire
     */
    public function setVetements($vetements)
    {
        $this->vetements = $vetements;

        return $this;
    }

    /**
     * Get the value of vetements.
     *
     * @return string
     */
    public function getVetements()
    {
        return $this->vetements;
    }

    /**
     * Set the value of noms_masculin.
     *
     * @param string $noms_masculin
     * @return \App\Entity\Territoire
     */
    public function setNomsMasculin($noms_masculin)
    {
        $this->noms_masculin = $noms_masculin;

        return $this;
    }

    /**
     * Get the value of noms_masculin.
     *
     * @return string
     */
    public function getNomsMasculin()
    {
        return $this->noms_masculin;
    }

    /**
     * Set the value of noms_feminin.
     *
     * @param string $noms_feminin
     * @return \App\Entity\Territoire
     */
    public function setNomsFeminin($noms_feminin)
    {
        $this->noms_feminin = $noms_feminin;

        return $this;
    }

    /**
     * Get the value of noms_feminin.
     *
     * @return string
     */
    public function getNomsFeminin()
    {
        return $this->noms_feminin;
    }

    /**
     * Set the value of frontieres.
     *
     * @param string $frontieres
     * @return \App\Entity\Territoire
     */
    public function setFrontieres($frontieres)
    {
        $this->frontieres = $frontieres;

        return $this;
    }

    /**
     * Get the value of frontieres.
     *
     * @return string
     */
    public function getFrontieres()
    {
        return $this->frontieres;
    }

    /**
     * Set the value of geojson.
     *
     * @param string $geojson
     * @return \App\Entity\Territoire
     */
    public function setGeojson($geojson)
    {
        $this->geojson = $geojson;

        return $this;
    }

    /**
     * Get the value of geojson.
     *
     * @return string
     */
    public function getGeojson()
    {
        return $this->geojson;
    }

    /**
     * Set the value of color.
     *
     * @param string $color
     * @return \App\Entity\Territoire
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get the value of color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the value of tresor.
     *
     * @param integer $tresor
     * @return \App\Entity\Territoire
     */
    public function setTresor($tresor)
    {
        $this->tresor = $tresor;

        return $this;
    }

    /**
     * Get the value of tresor.
     *
     * @return integer
     */
    public function getTresor()
    {
        return $this->tresor;
    }

    /**
     * Set the value of resistance.
     *
     * @param integer $resistance
     * @return \App\Entity\Territoire
     */
    public function setResistance($resistance)
    {
        $this->resistance = $resistance;

        return $this;
    }

    /**
     * Get the value of resistance.
     *
     * @return integer
     */
    public function getResistance()
    {
        return $this->resistance;
    }

    /**
     * Set the value of blason.
     *
     * @param string $blason
     * @return \App\Entity\Territoire
     */
    public function setBlason($blason)
    {
        $this->blason = $blason;

        return $this;
    }

    /**
     * Get the value of blason.
     *
     * @return string
     */
    public function getBlason()
    {
        return $this->blason;
    }

    /**
     * Set the value of description_secrete.
     *
     * @param string $description_secrete
     * @return \App\Entity\Territoire
     */
    public function setDescriptionSecrete($description_secrete)
    {
        $this->description_secrete = $description_secrete;

        return $this;
    }

    /**
     * Get the value of description_secrete.
     *
     * @return string
     */
    public function getDescriptionSecrete()
    {
        return $this->description_secrete;
    }

    /**
     * Set the value of statut.
     *
     * @param string $statut
     * @return \App\Entity\Territoire
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get the value of statut.
     *
     * @return string
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set the value of ordre_social.
     *
     * @param integer $ordre_social
     * @return \App\Entity\Territoire
     */
    public function setOrdreSocial($ordre_social)
    {
        $this->ordre_social = $ordre_social;

        return $this;
    }

    /**
     * Get the value of ordre_social.
     *
     * @return integer
     */
    public function getOrdreSocial()
    {
        return $this->ordre_social;
    }

    /**
     * Add Chronologie entity to collection (one to many).
     *
     * @param \App\Entity\Chronologie $chronologie
     * @return \App\Entity\Territoire
     */
    public function addChronologie(Chronologie $chronologie)
    {
        $this->chronologies[] = $chronologie;

        return $this;
    }

    /**
     * Remove Chronologie entity from collection (one to many).
     *
     * @param \App\Entity\Chronologie $chronologie
     * @return \App\Entity\Territoire
     */
    public function removeChronologie(Chronologie $chronologie)
    {
        $this->chronologies->removeElement($chronologie);

        return $this;
    }

    /**
     * Get Chronologie entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChronologies()
    {
        return $this->chronologies;
    }

    /**
     * Add Territoire entity to collection (many to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function addTerritoireCible(Territoire $territoire)
    {
	$this->territoireCibles[] = $territoire;

	return $this;
    }

    /**
     * Remove Territoire entity from collection (many to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function removeTerritoireCible(Territoire $territoire)
    {
    	$this->territoireCibles->removeElement($territoire);

	return $this;
    }

    /**
     * Get territoireCibles entity collection
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoireCibles()
    {
	return $this->territoireCibles;
    }

    /**
     * Add Territoire entity to collection (many to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function addTerritoireStart(Territoire $territoire)
    {
	$this->territoireStarts[] = $territoire;

	return $this;
    }

    /**
     * Remove Territoire entity from collection (many to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function removeTerritoireStart(Territoire $territoire)
    {
	$this->territoireStarts->removeElement($territoire);

	return $this;
    }

    /**
     * Get TerritoireStarts entity collection
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoireStarts()
    {
	return $this->territoireStarts;
    }

    /**
     * Add Groupe entity to collection (one to many).
     *
     * @param \App\Entity\Groupe $groupe
     * @return \App\Entity\Territoire
     */
    public function addGroupe(Groupe $groupe)
    {
        $this->groupes[] = $groupe;

        return $this;
    }

    /**
     * Remove Groupe entity from collection (one to many).
     *
     * @param \App\Entity\Groupe $groupe
     * @return \App\Entity\Territoire
     */
    public function removeGroupe(Groupe $groupe)
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    /**
     * Get Groupe entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupes()
    {
        return $this->groupes;
    }

    /**
     * Add Personnage entity to collection (one to many).
     *
     * @param \App\Entity\Personnage $personnage
     * @return \App\Entity\Territoire
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection (one to many).
     *
     * @param \App\Entity\Personnage $personnage
     * @return \App\Entity\Territoire
     */
    public function removePersonnage(Personnage $personnage)
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
     * Add Rumeur entity to collection (one to many).
     *
     * @param \App\Entity\Rumeur $rumeur
     * @return \App\Entity\Territoire
     */
    public function addRumeur(Rumeur $rumeur)
    {
        $this->rumeurs[] = $rumeur;

        return $this;
    }

    /**
     * Remove Rumeur entity from collection (one to many).
     *
     * @param \App\Entity\Rumeur $rumeur
     * @return \App\Entity\Territoire
     */
    public function removeRumeur(Rumeur $rumeur)
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    /**
     * Get Rumeur entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRumeurs()
    {
        return $this->rumeurs;
    }

    /**
     * Add Territoire entity to collection (one to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function addTerritoire(Territoire $territoire)
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
     */
    public function removeTerritoire(Territoire $territoire)
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoires()
    {
        return $this->territoires;
    }

    /**
     * Add TitreTerritoire entity to collection (one to many).
     *
     * @param \App\Entity\TitreTerritoire $titreTerritoire
     * @return \App\Entity\Territoire
     */
    public function addTitreTerritoire(TitreTerritoire $titreTerritoire)
    {
        $this->titreTerritoires[] = $titreTerritoire;

        return $this;
    }

    /**
     * Remove TitreTerritoire entity from collection (one to many).
     *
     * @param \App\Entity\TitreTerritoire $titreTerritoire
     * @return \App\Entity\Territoire
     */
    public function removeTitreTerritoire(TitreTerritoire $titreTerritoire)
    {
        $this->titreTerritoires->removeElement($titreTerritoire);

        return $this;
    }

    /**
     * Get TitreTerritoire entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTitreTerritoires()
    {
        return $this->titreTerritoires;
    }

    /**
     * Set Territoire entity (many to one).
     *
     * @param \App\Entity\Territoire $territoire
     * @return \App\Entity\Territoire
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
     * Set TerritoireGuerre entity (one to one).
     *
     * @param \App\Entity\TerritoireGuerre $territoireGuerre
     * @return \App\Entity\Territoire
     */
    public function setTerritoireGuerre(TerritoireGuerre $territoireGuerre)
    {
        $this->territoireGuerre = $territoireGuerre;

        return $this;
    }

    /**
     * Get TerritoireGuerre entity (one to one).
     *
     * @return \App\Entity\TerritoireGuerre
     */
    public function getTerritoireGuerre()
    {
        return $this->territoireGuerre;
    }

    /**
     * Set Appelation entity (many to one).
     *
     * @param \App\Entity\Appelation $appelation
     * @return \App\Entity\Territoire
     */
    public function setAppelation(Appelation $appelation = null)
    {
        $this->appelation = $appelation;

        return $this;
    }

    /**
     * Get Appelation entity (many to one).
     *
     * @return \App\Entity\Appelation
     */
    public function getAppelation()
    {
        return $this->appelation;
    }

    /**
     * Set Langue entity (many to one).
     *
     * @param \App\Entity\Langue $langue
     * @return \App\Entity\Territoire
     */
    public function setLangue(Langue $langue = null)
    {
        $this->langue = $langue;

        return $this;
    }

    /**
     * Get Langue entity (many to one).
     *
     * @return \App\Entity\Langue
     */
    public function getLangue()
    {
        return $this->langue;
    }

    /**
     * Set Topic entity (many to one).
     *
     * @param \App\Entity\Topic $topic
     * @return \App\Entity\Territoire
     */
    public function setTopic(Topic $topic = null)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     *
     * @return \App\Entity\Topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set Religion entity (many to one).
     *
     * @param \App\Entity\Religion $religion
     * @return \App\Entity\Territoire
     */
    public function setReligion(Religion $religion = null)
    {
        $this->religion = $religion;

        return $this;
    }

    /**
     * Get Religion entity (many to one).
     *
     * @return \App\Entity\Religion
     */
    public function getReligion()
    {
        return $this->religion;
    }

    /**
     * Set Groupe entity (many to one).
     *
     * @param \App\Entity\Groupe $groupe
     * @return \App\Entity\Territoire
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
     * Set Culture entity (many to one).
     *
     * @param \App\Entity\Culture $culture
     * @return \App\Entity\Territoire
     */
    public function setCulture(Culture $culture = null)
    {
        $this->culture = $culture;

        return $this;
    }

    /**
     * Get Culture entity (many to one).
     *
     * @return \App\Entity\Culture
     */
    public function getCulture()
    {
        return $this->culture;
    }

    /**
     * Add Construction entity to collection.
     *
     * @param \App\Entity\Construction $construction
     * @return \App\Entity\Territoire
     */
    public function addConstruction(Construction $construction)
    {
        $construction->addTerritoire($this);
        $this->constructions[] = $construction;

        return $this;
    }

    /**
     * Remove Construction entity from collection.
     *
     * @param \App\Entity\Construction $construction
     * @return \App\Entity\Territoire
     */
    public function removeConstruction(Construction $construction)
    {
        $construction->removeTerritoire($this);
        $this->constructions->removeElement($construction);

        return $this;
    }

    /**
     * Get Construction entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConstructions()
    {
        return $this->constructions;
    }

    /**
     * Add Loi entity to collection.
     *
     * @param \App\Entity\Loi $loi
     * @return \App\Entity\Territoire
     */
    public function addLoi(Loi $loi)
    {
        $loi->addTerritoire($this);
        $this->lois[] = $loi;

        return $this;
    }

    /**
     * Remove Loi entity from collection.
     *
     * @param \App\Entity\Loi $loi
     * @return \App\Entity\Territoire
     */
    public function removeLoi(Loi $loi)
    {
        $loi->removeTerritoire($this);
        $this->lois->removeElement($loi);

        return $this;
    }

    /**
     * Get Loi entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLois()
    {
        return $this->lois;
    }

    /**
     * Add Ingredient entity to collection.
     *
     * @param \App\Entity\Ingredient $ingredient
     * @return \App\Entity\Territoire
     */
    public function addIngredient(Ingredient $ingredient)
    {
        $ingredient->addTerritoire($this);
        $this->ingredients[] = $ingredient;

        return $this;
    }

    /**
     * Remove Ingredient entity from collection.
     *
     * @param \App\Entity\Ingredient $ingredient
     * @return \App\Entity\Territoire
     */
    public function removeIngredient(Ingredient $ingredient)
    {
        $ingredient->removeTerritoire($this);
        $this->ingredients->removeElement($ingredient);

        return $this;
    }

    /**
     * Get Ingredient entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIngredients()
    {
        return $this->ingredients;
    }

    public function __sleep()
    {
        return array('id', 'nom', 'description', 'capitale', 'politique', 'dirigeant', 'population', 'symbole', 'tech_level', 'territoire_id', 'territoire_guerre_id', 'appelation_id', 'langue_id', 'topic_id', 'religion_id', 'type_racial', 'inspiration', 'armes_predilection', 'vetements', 'noms_masculin', 'noms_feminin', 'frontieres', 'geojson', 'color', 'groupe_id', 'tresor', 'resistance', 'blason', 'description_secrete', 'statut', 'culture_id', 'ordre_social');
    }
}
