<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * App\Entity\Objet.
 *
 * @Table(name="objet", indexes={@Index(name="fk_objet_etat1_idx", columns={"etat_id"}), @Index(name="fk_objet_possesseur1_idx", columns={"proprietaire_id"}), @Index(name="fk_objet_Users1_idx", columns={"responsable_id"}), @Index(name="fk_objet_photo1_idx", columns={"photo_id"}), @Index(name="fk_objet_rangement1_idx", columns={"rangement_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseObjet", "extended":"Objet"})
 */
class BaseObjet
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $objetCaracs;
    #[ORM\Id, ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER), ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $numero;

    /**
     * @Column(type="string", length=100)
     */
    protected $nom;

    /**
     * @Column(type="string", length=450, nullable=true)
     */
    protected $description;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $nombre;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $cout;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $budget;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $investissement;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $creation_date;

    /**
     * @OneToMany(targetEntity="Item", mappedBy="objet")
     *
     * @JoinColumn(name="id", referencedColumnName="objet_id", nullable=false)
     */
    protected $items;

    /**
     * @OneToOne(targetEntity="ObjetCarac", mappedBy="objet", cascade={"persist", "merge", "remove", "detach", "all"})
     */
    protected $objetCarac;

    /**
     * @ManyToOne(targetEntity="Etat", inversedBy="objets")
     *
     * @JoinColumn(name="etat_id", referencedColumnName="id")
     */
    protected $etat;

    /**
     * @ManyToOne(targetEntity="Proprietaire", inversedBy="objets")
     *
     * @JoinColumn(name="proprietaire_id", referencedColumnName="id")
     */
    protected $proprietaire;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'objets')]
    #[JoinColumn(name: 'responsable_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    /**
     * @ManyToOne(targetEntity="Photo", inversedBy="objets", cascade={"persist", "merge", "remove", "detach", "all"})
     *
     * @JoinColumn(name="photo_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $photo;

    /**
     * @ManyToOne(targetEntity="Rangement", inversedBy="objets")
     *
     * @JoinColumn(name="rangement_id", referencedColumnName="id", nullable=false)
     */
    protected $rangement;

    /**
     * @ManyToMany(targetEntity="Tag", inversedBy="objets")
     *
     * @JoinTable(name="objet_tag",
     *     joinColumns={@JoinColumn(name="objet_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="tag_id", referencedColumnName="id", nullable=false)}
     * )
     */
    protected $tags;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->objetCaracs = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @return \App\Entity\Objet
     */
    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of numero.
     *
     * @param string $numero
     *
     * @return \App\Entity\Objet
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     *
     * @return string
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Set the value of nom.
     *
     * @param string $nom
     *
     * @return \App\Entity\Objet
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
     *
     * @return \App\Entity\Objet
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
     * Set the value of nombre.
     *
     * @param int $nombre
     *
     * @return \App\Entity\Objet
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     *
     * @return int
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * Set the value of cout.
     *
     * @param float $cout
     *
     * @return \App\Entity\Objet
     */
    public function setCout($cout)
    {
        $this->cout = $cout;

        return $this;
    }

    /**
     * Get the value of cout.
     *
     * @return float
     */
    public function getCout()
    {
        return $this->cout;
    }

    /**
     * Set the value of budget.
     *
     * @param float $budget
     *
     * @return \App\Entity\Objet
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get the value of budget.
     *
     * @return float
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * Set the value of investissement.
     *
     * @param bool $investissement
     *
     * @return \App\Entity\Objet
     */
    public function setInvestissement($investissement)
    {
        $this->investissement = $investissement;

        return $this;
    }

    /**
     * Get the value of investissement.
     *
     * @return bool
     */
    public function getInvestissement()
    {
        return $this->investissement;
    }

    /**
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return \App\Entity\Objet
     */
    public function setCreationDate($creation_date)
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

    /**
     * Add Item entity to collection (one to many).
     *
     * @return \App\Entity\Objet
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection (one to many).
     *
     * @return \App\Entity\Objet
     */
    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set ObjetCarac entity (one to one).
     *
     * @return \App\Entity\Objet
     */
    public function setObjetCarac(ObjetCarac $objetCarac = null)
    {
        $objetCarac->setObjet($this);
        $this->objetCarac = $objetCarac;

        return $this;
    }

    /**
     * Get ObjetCarac entity (one to one).
     *
     * @return \App\Entity\ObjetCarac
     */
    public function getObjetCarac()
    {
        return $this->objetCarac;
    }

    /**
     * Set Etat entity (many to one).
     *
     * @return \App\Entity\Objet
     */
    public function setEtat(Etat $etat = null)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get Etat entity (many to one).
     *
     * @return \App\Entity\Etat
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set Proprietaire entity (many to one).
     *
     * @return \App\Entity\Objet
     */
    public function setProprietaire(Proprietaire $proprietaire = null)
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    /**
     * Get Proprietaire entity (many to one).
     *
     * @return \App\Entity\Proprietaire
     */
    public function getProprietaire()
    {
        return $this->proprietaire;
    }

    /**
     * Set User entity (many to one).
     *
     * @return \App\Entity\Objet
     */
    public function setUser(User $User = null)
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set Photo entity (many to one).
     *
     * @return \App\Entity\Objet
     */
    public function setPhoto(Photo $photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get Photo entity (many to one).
     *
     * @return \App\Entity\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set Rangement entity (many to one).
     *
     * @return \App\Entity\Objet
     */
    public function setRangement(Rangement $rangement = null)
    {
        $this->rangement = $rangement;

        return $this;
    }

    /**
     * Get Rangement entity (many to one).
     *
     * @return \App\Entity\Rangement
     */
    public function getRangement()
    {
        return $this->rangement;
    }

    /**
     * Add Tag entity to collection.
     *
     * @return \App\Entity\Objet
     */
    public function addTag(Tag $tag)
    {
        $tag->addObjet($this);
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove Tag entity from collection.
     *
     * @return \App\Entity\Objet
     */
    public function removeTag(Tag $tag)
    {
        $tag->removeObjet($this);
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Get Tag entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function __sleep()
    {
        return ['id', 'numero', 'nom', 'description', 'etat_id', 'proprietaire_id', 'responsable_id', 'nombre', 'cout', 'budget', 'investissement', 'creation_date', 'photo_id', 'rangement_id'];
    }
}
