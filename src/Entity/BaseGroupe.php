<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
#[ORM\Table(name: 'groupe')]
#[ORM\Index(columns: ['scenariste_id'], name: 'fk_groupe_users1_idx')]
#[ORM\Index(columns: ['responsable_id'], name: 'fk_groupe_user2_idx')]
#[ORM\Index(columns: ['topic_id'], name: 'fk_groupe_topic1_idx')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_groupe_territoire1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupe', 'extended' => 'Groupe'])]
class BaseGroupe
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected ?string $nom = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $numero;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true)]
    protected ?string $code = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $jeu_maritime = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $jeu_strategique = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $classe_open = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $pj = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?bool $materiel = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $lock;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $richesse = null;

    #[OneToMany(mappedBy: 'groupe', targetEntity: Background::class)]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id')]
    protected Collection $backgrounds;

    #[OneToMany(mappedBy: 'groupe', targetEntity: Debriefing::class)]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected Collection $debriefings;

    #[OneToMany(mappedBy: 'groupeRelatedByGroupeId', targetEntity: GroupeAllie::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeAllieRelatedByGroupeIds;

    #[OneToMany(mappedBy: 'groupeRelatedByGroupeAllieId', targetEntity: GroupeAllie::class)]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'groupe_allie_id', nullable: false)]
    protected Collection $groupeAllieRelatedByGroupeAllieIds;

    #[OneToMany(mappedBy: 'groupe', targetEntity: GroupeClasse::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeClasses;

    #[OneToMany(mappedBy: 'groupeRelatedByGroupeId', targetEntity: GroupeEnemy::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeEnemyRelatedByGroupeIds;

    #[OneToMany(mappedBy: 'groupeRelatedByGroupeEnemyId', targetEntity: GroupeEnemy::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_enemy_id', nullable: false)]
    protected Collection $groupeEnemyRelatedByGroupeEnemyIds;

    #[OneToMany(mappedBy: 'groupe', targetEntity: GroupeGn::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeGns;

    #[OneToMany(mappedBy: 'groupe', targetEntity: GroupeHasIngredient::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeHasIngredients;

    #[OneToMany(mappedBy: 'groupe', targetEntity: GroupeHasRessource::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $groupeHasRessources;

    #[OneToMany(mappedBy: 'groupe', targetEntity: IntrigueHasGroupe::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $intrigueHasGroupes;

    #[OneToMany(mappedBy: 'groupe', targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $personnages;

    #[OneToMany(mappedBy: 'groupe', targetEntity: Territoire::class)]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'groupe_id', nullable: false)]
    protected Collection $territoires;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'groupeRelatedByScenaristeIds')]
    #[ORM\JoinColumn(name: 'scenariste_id', referencedColumnName: 'id')]
    protected ?User $userRelatedByScenaristeId = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'groupeRelatedByResponsableIds')]
    #[ORM\JoinColumn(name: 'responsable_id', referencedColumnName: 'id')]
    protected ?User $userRelatedByResponsableId = null;

    #[ManyToOne(targetEntity: Topic::class, inversedBy: 'groupes')]
    #[JoinColumn(name: 'topic_id', referencedColumnName: 'id', nullable: 'false')]
    protected Topic $topic;

    #[ManyToOne(targetEntity: Territoire::class, inversedBy: 'groupes')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: 'false')]
    protected Territoire $territoire;

    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'groupes')]
    #[ORM\JoinTable(name: 'groupe_has_document')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'document_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OrderBy(['code' => 'ASC'])]
    protected Collection $documents;

    #[ORM\ManyToMany(targetEntity: Item::class, inversedBy: 'groupes')]
    #[ORM\JoinTable(name: 'groupe_has_item')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OrderBy(['label' => 'ASC'])]
    protected Collection $items;

    public function __construct()
    {
        $this->backgrounds = new ArrayCollection();
        $this->debriefings = new ArrayCollection();
        $this->groupeAllieRelatedByGroupeIds = new ArrayCollection();
        $this->groupeAllieRelatedByGroupeAllieIds = new ArrayCollection();
        $this->groupeClasses = new ArrayCollection();
        $this->groupeEnemyRelatedByGroupeIds = new ArrayCollection();
        $this->groupeEnemyRelatedByGroupeEnemyIds = new ArrayCollection();
        $this->groupeGns = new ArrayCollection();
        $this->groupeHasIngredients = new ArrayCollection();
        $this->groupeHasRessources = new ArrayCollection();
        $this->intrigueHasGroupes = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->items = new ArrayCollection();
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
     * Set the value of numero.
     */
    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get the value of numero.
     */
    public function getNumero(): string
    {
        return $this->numero;
    }

    /**
     * Set the value of code.
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set the value of jeu_maritime.
     */
    public function setJeuMaritime(bool $jeu_maritime): static
    {
        $this->jeu_maritime = $jeu_maritime;

        return $this;
    }

    /**
     * Get the value of jeu_maritime.
     */
    public function getJeuMaritime(): bool
    {
        return $this->jeu_maritime;
    }

    /**
     * Set the value of jeu_strategique.
     */
    public function setJeuStrategique(bool $jeu_strategique): static
    {
        $this->jeu_strategique = $jeu_strategique;

        return $this;
    }

    /**
     * Get the value of jeu_strategique.
     */
    public function getJeuStrategique(): bool
    {
        return $this->jeu_strategique;
    }

    /**
     * Set the value of classe_open.
     */
    public function setClasseOpen(int $classe_open): static
    {
        $this->classe_open = $classe_open;

        return $this;
    }

    /**
     * Get the value of classe_open.
     */
    public function getClasseOpen(): int
    {
        return $this->classe_open;
    }

    /**
     * Set the value of pj.
     */
    public function setPj(bool $pj): static
    {
        $this->pj = $pj;

        return $this;
    }

    /**
     * Get the value of pj.
     */
    public function getPj(): bool
    {
        return $this->pj;
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
     * Get the value of materiel.
     */
    public function getMateriel(): string
    {
        return $this->materiel ?? '';
    }

    /**
     * Set the value of lock.
     */
    public function setLock(bool $lock): static
    {
        $this->lock = $lock;

        return $this;
    }

    /**
     * Get the value of lock.
     */
    public function getLock(): bool
    {
        return $this->lock;
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
     * Get the value of richesse.
     */
    public function getRichesse(): int
    {
        return $this->richesse;
    }

    /**
     * Add Background entity to collection (one to many).
     */
    public function addBackground(Background $background): static
    {
        $this->backgrounds[] = $background;

        return $this;
    }

    /**
     * Remove Background entity from collection (one to many).
     */
    public function removeBackground(Background $background): static
    {
        $this->backgrounds->removeElement($background);

        return $this;
    }

    /**
     * Get Background entity collection (one to many).
     */
    public function getBackgrounds(): Collection
    {
        return $this->backgrounds;
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
     */
    public function getDebriefings(): Collection
    {
        return $this->debriefings;
    }

    /**
     * Add GroupeAllie entity related by `groupe_id` to collection (one to many).
     */
    public function addGroupeAllieRelatedByGroupeId(GroupeAllie $groupeAllie): static
    {
        $this->groupeAllieRelatedByGroupeIds[] = $groupeAllie;

        return $this;
    }

    /**
     * Remove GroupeAllie entity related by `groupe_id` from collection (one to many).
     */
    public function removeGroupeAllieRelatedByGroupeId(GroupeAllie $groupeAllie): static
    {
        $this->groupeAllieRelatedByGroupeIds->removeElement($groupeAllie);

        return $this;
    }

    /**
     * Get GroupeAllie entity related by `groupe_id` collection (one to many).
     */
    public function getGroupeAllieRelatedByGroupeIds(): Collection
    {
        return $this->groupeAllieRelatedByGroupeIds;
    }

    /**
     * Add GroupeAllie entity related by `groupe_allie_id` to collection (one to many).
     */
    public function addGroupeAllieRelatedByGroupeAllieId(GroupeAllie $groupeAllie): static
    {
        $this->groupeAllieRelatedByGroupeAllieIds[] = $groupeAllie;

        return $this;
    }

    /**
     * Remove GroupeAllie entity related by `groupe_allie_id` from collection (one to many).
     */
    public function removeGroupeAllieRelatedByGroupeAllieId(GroupeAllie $groupeAllie): static
    {
        $this->groupeAllieRelatedByGroupeAllieIds->removeElement($groupeAllie);

        return $this;
    }

    /**
     * Get GroupeAllie entity related by `groupe_allie_id` collection (one to many).
     */
    public function getGroupeAllieRelatedByGroupeAllieIds(): Collection
    {
        return $this->groupeAllieRelatedByGroupeAllieIds;
    }

    /**
     * Add GroupeClasse entity to collection (one to many).
     */
    public function addGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses[] = $groupeClasse;

        return $this;
    }

    /**
     * Remove GroupeClasse entity from collection (one to many).
     */
    public function removeGroupeClasse(GroupeClasse $groupeClasse): static
    {
        $this->groupeClasses->removeElement($groupeClasse);

        return $this;
    }

    /**
     * Get GroupeClasse entity collection (one to many).
     */
    public function getGroupeClasses(): Collection
    {
        return $this->groupeClasses;
    }

    /**
     * Add GroupeEnemy entity related by `groupe_id` to collection (one to many).
     */
    public function addGroupeEnemyRelatedByGroupeId(GroupeEnemy $groupeEnemy): static
    {
        $this->groupeEnemyRelatedByGroupeIds[] = $groupeEnemy;

        return $this;
    }

    /**
     * Remove GroupeEnemy entity related by `groupe_id` from collection (one to many).
     */
    public function removeGroupeEnemyRelatedByGroupeId(GroupeEnemy $groupeEnemy): static
    {
        $this->groupeEnemyRelatedByGroupeIds->removeElement($groupeEnemy);

        return $this;
    }

    /**
     * Get GroupeEnemy entity related by `groupe_id` collection (one to many).
     */
    public function getGroupeEnemyRelatedByGroupeIds(): Collection
    {
        return $this->groupeEnemyRelatedByGroupeIds;
    }

    /**
     * Add GroupeEnemy entity related by `groupe_enemy_id` to collection (one to many).
     */
    public function addGroupeEnemyRelatedByGroupeEnemyId(GroupeEnemy $groupeEnemy): static
    {
        $this->groupeEnemyRelatedByGroupeEnemyIds[] = $groupeEnemy;

        return $this;
    }

    /**
     * Remove GroupeEnemy entity related by `groupe_enemy_id` from collection (one to many).
     */
    public function removeGroupeEnemyRelatedByGroupeEnemyId(GroupeEnemy $groupeEnemy): static
    {
        $this->groupeEnemyRelatedByGroupeEnemyIds->removeElement($groupeEnemy);

        return $this;
    }

    /**
     * Get GroupeEnemy entity related by `groupe_enemy_id` collection (one to many).
     */
    public function getGroupeEnemyRelatedByGroupeEnemyIds(): Collection
    {
        return $this->groupeEnemyRelatedByGroupeEnemyIds;
    }

    /**
     * Add GroupeGn entity to collection (one to many).
     */
    public function addGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns[] = $groupeGn;

        return $this;
    }

    /**
     * Remove GroupeGn entity from collection (one to many).
     */
    public function removeGroupeGn(GroupeGn $groupeGn): static
    {
        $this->groupeGns->removeElement($groupeGn);

        return $this;
    }

    /**
     * Get GroupeGn entity collection (one to many).
     */
    public function getGroupeGns(): Collection
    {
        return $this->groupeGns;
    }

    /**
     * Add GroupeHasIngredient entity to collection (one to many).
     */
    public function addGroupeHasIngredient(GroupeHasIngredient $groupeHasIngredient): static
    {
        $this->groupeHasIngredients[] = $groupeHasIngredient;

        return $this;
    }

    /**
     * Remove GroupeHasIngredient entity from collection (one to many).
     */
    public function removeGroupeHasIngredient(GroupeHasIngredient $groupeHasIngredient): static
    {
        $this->groupeHasIngredients->removeElement($groupeHasIngredient);

        return $this;
    }

    /**
     * Get GroupeHasIngredient entity collection (one to many).
     */
    public function getGroupeHasIngredients(): Collection
    {
        return $this->groupeHasIngredients;
    }

    /**
     * Add GroupeHasRessource entity to collection (one to many).
     */
    public function addGroupeHasRessource(GroupeHasRessource $groupeHasRessource): static
    {
        $this->groupeHasRessources[] = $groupeHasRessource;

        return $this;
    }

    /**
     * Remove GroupeHasRessource entity from collection (one to many).
     */
    public function removeGroupeHasRessource(GroupeHasRessource $groupeHasRessource): static
    {
        $this->groupeHasRessources->removeElement($groupeHasRessource);

        return $this;
    }

    /**
     * Get GroupeHasRessource entity collection (one to many).
     */
    public function getGroupeHasRessources(): Collection
    {
        return $this->groupeHasRessources;
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
    public function getIntrigueHasGroupes(): Collection
    {
        return $this->intrigueHasGroupes;
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
     * Set User entity related by `scenariste_id` (many to one).
     */
    public function setUserRelatedByScenaristeId(User $User = null): static
    {
        $this->userRelatedByScenaristeId = $User;

        return $this;
    }

    /**
     * Get User entity related by `scenariste_id` (many to one).
     */
    public function getUserRelatedByScenaristeId(): ?User
    {
        return $this->userRelatedByScenaristeId;
    }

    /**
     * Set User entity related by `responsable_id` (many to one).
     */
    public function setUserRelatedByResponsableId(User $User = null): static
    {
        $this->userRelatedByResponsableId = $User;

        return $this;
    }

    /**
     * Get User entity related by `responsable_id` (many to one).
     */
    public function getUserRelatedByResponsableId(): ?User
    {
        return $this->userRelatedByResponsableId;
    }

    /**
     * Set Topic entity (many to one).
     */
    public function setTopic(Topic $topic = null): static
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     */
    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    /**
     * Set Territoire entity (many to one).
     */
    public function setTerritoire(Territoire $territoire = null): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (many to one).
     */
    public function getTerritoire()
    {
        return $this->territoire;
    }

    /**
     * Add Document entity to collection.
     */
    public function addDocument(Document $document): static
    {
        $document->addGroupe($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove Document entity from collection.
     */
    public function removeDocument(Document $document): static
    {
        $document->removeGroupe($this);
        $this->documents->removeElement($document);

        return $this;
    }

    /**
     * Get Document entity collection.
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Add Item entity to collection.
     */
    public function addItem(Item $item): static
    {
        $item->addGroupe($this);
        $this->items[] = $item;

        return $this;
    }

    /**
     * Remove Item entity from collection.
     */
    public function removeItem(Item $item): static
    {
        $item->removeGroupe($this);
        $this->items->removeElement($item);

        return $this;
    }

    /**
     * Get Item entity collection.
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function __sleep()
    {
        return ['id', 'nom', 'description', 'numero', 'code', 'jeu_maritime', 'jeu_strategique', 'scenariste_id', 'classe_open', 'responsable_id', 'topic_id', 'pj', 'materiel', 'lock', 'territoire_id', 'richesse'];
    }
}
