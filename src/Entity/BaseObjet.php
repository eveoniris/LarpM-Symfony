<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'objet')]
#[ORM\Index(columns: ['etat_id'], name: 'fk_objet_etat1_idx')]
#[ORM\Index(columns: ['proprietaire_id'], name: 'fk_objet_possesseur1_idx')]
#[ORM\Index(columns: ['responsable_id'], name: 'fk_objet_users1_idx')]
#[ORM\Index(columns: ['photo_id'], name: 'fk_objet_photo1_idx')]
#[ORM\Index(columns: ['rangement_id'], name: 'fk_objet_rangement1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseObjet', 'extended' => 'Objet'])]
abstract class BaseObjet
{
    #[ORM\Id, ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER), ORM\GeneratedValue]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $numero = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100)]
    protected string $nom = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $nombre = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $cout = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $budget = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $investissement = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'objet')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'objet_id', nullable: 'false')]
    protected Collection $items;

    #[ORM\OneToOne(targetEntity: ObjetCarac::class, inversedBy: 'objet', cascade: ['persist', 'remove', 'detach', 'all'])]
    #[ORM\JoinColumn(name: 'objet_id', referencedColumnName: 'id')]
    protected Collection $objetCaracs;

    #[ORM\ManyToOne(targetEntity: Etat::class, inversedBy: 'objets')]
    #[ORM\JoinColumn(name: 'etat_id', referencedColumnName: 'id')]
    protected Etat $etat;

    #[ManyToOne(targetEntity: Proprietaire::class, inversedBy: 'objets')]
    #[JoinColumn(name: 'proprietaire_id', referencedColumnName: 'id', nullable: 'false')]
    protected Proprietaire $proprietaire;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'objets')]
    #[JoinColumn(name: 'responsable_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    #[ManyToOne(targetEntity: Photo::class, inversedBy: 'objets', cascade: ['persist', 'merge', 'remove', 'detach', 'all'])]
    #[JoinColumn(name: 'photo_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Photo $photo;

    #[ManyToOne(targetEntity: Rangement::class, cascade: ['persist', 'merge', 'remove', 'detach', 'all'], inversedBy: 'objets')]
    #[JoinColumn(name: 'rangement_id', referencedColumnName: 'id', nullable: 'false')]
    protected Rangement $rangement;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'objets')]
    #[ORM\JoinTable(name: 'objet_tag')]
    #[ORM\JoinColumn(name: 'objet_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $tags;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->objetCaracs = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * Set the value of id.
     */
    public function setId(?int $id): static
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
     * Set the value of numero.
     */
    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     */
    public function getNumero(): string
    {
        return $this->numero ?? '';
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
     * Set the value of nombre.
     */
    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     */
    public function getNombre(): int
    {
        return $this->nombre;
    }

    /**
     * Set the value of cout.
     */
    public function setCout(float $cout): static
    {
        $this->cout = $cout;

        return $this;
    }

    /**
     * Get the value of cout.
     */
    public function getCout(): float
    {
        return $this->cout;
    }

    /**
     * Set the value of budget.
     */
    public function setBudget(float $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get the value of budget.
     */
    public function getBudget(): float
    {
        return $this->budget;
    }

    /**
     * Set the value of investissement.
     */
    public function setInvestissement(bool $investissement): static
    {
        $this->investissement = $investissement;

        return $this;
    }

    /**
     * Get the value of investissement.
     */
    public function getInvestissement(): bool
    {
        return $this->investissement;
    }

    /**
     * Set the value of creation_date.
     */
    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    /**
     * Add Item entity to collection (one to many).
     */
    public function addItem(Item $item): Collection
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection (one to many).
     */
    public function removeItem(Item $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection (one to many).
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Set ObjetCarac entity (one to one).
     */
    public function setObjetCarac(ObjetCarac $objetCarac = null): static
    {
        if (null !== $objetCarac) {
            $objetCarac->setObjet($this);
        }
        $this->objetCarac = $objetCarac;

        return $this;
    }

    /**
     * Get ObjetCarac entity (one to one).
     */
    public function getObjetCarac(): ObjetCarac
    {
        return $this->objetCarac;
    }

    /**
     * Set Etat entity (many to one).
     */
    public function setEtat(Etat $etat = null): static
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get Etat entity (many to one).
     */
    public function getEtat(): Etat
    {
        return $this->etat;
    }

    /**
     * Set Proprietaire entity (many to one).
     */
    public function setProprietaire(Proprietaire $proprietaire = null): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    /**
     * Get Proprietaire entity (many to one).
     */
    public function getProprietaire(): Proprietaire
    {
        return $this->proprietaire;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set Photo entity (many to one).
     */
    public function setPhoto(Photo $photo = null): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get Photo entity (many to one).
     */
    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    /**
     * Set Rangement entity (many to one).
     */
    public function setRangement(Rangement $rangement = null): static
    {
        $this->rangement = $rangement;

        return $this;
    }

    /**
     * Get Rangement entity (many to one).
     */
    public function getRangement(): Rangement
    {
        return $this->rangement;
    }

    /**
     * Add Tag entity to collection.
     */
    public function addTag(Tag $tag): static
    {
        $tag->addObjet($this);
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove Tag entity from collection.
     */
    public function removeTag(Tag $tag): static
    {
        $tag->removeObjet($this);
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Get Tag entity collection.
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /* public function __sleep()
    {
        return ['id', 'numero', 'nom', 'description', 'etat_id', 'proprietaire_id', 'responsable_id', 'nombre', 'cout', 'budget', 'investissement', 'creation_date', 'photo_id', 'rangement_id'];
    } */
}
