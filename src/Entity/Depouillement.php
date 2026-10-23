<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Repository\DepouillementRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dépouillement d'une enveloppe de retour de jeu (joueur ou groupe) pour un GN.
 *
 * Tant que le statut n'est pas "valide", `donnees` est un brouillon de suivi : rien n'est appliqué aux données réelles.
 * Une simulation (`simulation` = true) n'a jamais d'effet à la validation et peut être purgée.
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: DepouillementRepository::class)]
#[ORM\Table(name: 'depouillement')]
#[ORM\Index(columns: ['gn_id', 'type', 'numero'], name: 'idx_depouillement_enveloppe')]
#[ORM\Index(columns: ['statut'], name: 'idx_depouillement_statut')]
#[ORM\Index(columns: ['simulation'], name: 'idx_depouillement_simulation')]
class Depouillement
{
    /** Message affiché (ou levé) quand on tente de modifier un dépouillement qui n'est plus en cours de saisie. */
    public const string MESSAGE_NON_MODIFIABLE = 'Ce dépouillement n\'est plus modifiable.';

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Gn $gn;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: DepouillementType::class)]
    private DepouillementType $type;

    /** Numéro de l'enveloppe (numéro du joueur ou du groupe, tel qu'inscrit sur la fiche de retour). */
    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $numero;

    /** Enveloppe de joueur : participant concerné. */
    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Participant $participant = null;

    /** Enveloppe de groupe : session du groupe concernée. */
    #[ORM\ManyToOne(targetEntity: GroupeGn::class)]
    #[ORM\JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GroupeGn $groupeGn = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: DepouillementStatut::class)]
    private DepouillementStatut $statut = DepouillementStatut::EN_DEPOUILLEMENT;

    /** Essai sans effet à la validation, purgeable. */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $simulation = false;

    /** Dépouilleur actuel (les précédents sont dans l'historique). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'depouilleur_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $depouilleur = null;

    /** Brouillon de saisie (contenu constaté de l'enveloppe), structure définie par les étapes suivantes. */
    #[ORM\Column(type: Types::JSON)]
    private array $donnees = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $validatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'validated_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $validatedBy = null;

    /**
     * EXTRA_LAZY : chaque action ajoute une ligne d'historique (avec le brouillon avant et après) sans avoir besoin de relire
     * toutes les précédentes.
     *
     * @var Collection<int, DepouillementHistory>
     */
    #[ORM\OneToMany(mappedBy: 'depouillement', targetEntity: DepouillementHistory::class, cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['actionDate' => 'DESC'])]
    private Collection $histories;

    /** @var Collection<int, DepouillementPhoto> */
    #[ORM\OneToMany(mappedBy: 'depouillement', targetEntity: DepouillementPhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $photos;

    /** @var Collection<int, Conception> conceptions d'héritier signalées dans ce retour de jeu */
    #[ORM\OneToMany(mappedBy: 'depouillement', targetEntity: Conception::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $conceptions;

    /** @var Collection<int, DepouillementEtiquette> */
    #[ORM\OneToMany(mappedBy: 'depouillement', targetEntity: DepouillementEtiquette::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $etiquettes;

    public function __construct()
    {
        $this->conceptions = new ArrayCollection();
        $this->etiquettes = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->histories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGn(): Gn
    {
        return $this->gn;
    }

    public function setGn(Gn $gn): static
    {
        $this->gn = $gn;

        return $this;
    }

    public function getType(): DepouillementType
    {
        return $this->type;
    }

    public function setType(DepouillementType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getGroupeGn(): ?GroupeGn
    {
        return $this->groupeGn;
    }

    public function setGroupeGn(?GroupeGn $groupeGn): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
    }

    public function getStatut(): DepouillementStatut
    {
        return $this->statut;
    }

    public function setStatut(DepouillementStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isSimulation(): bool
    {
        return $this->simulation;
    }

    public function setSimulation(bool $simulation): static
    {
        $this->simulation = $simulation;

        return $this;
    }

    public function getDepouilleur(): ?User
    {
        return $this->depouilleur;
    }

    public function setDepouilleur(?User $depouilleur): static
    {
        $this->depouilleur = $depouilleur;

        return $this;
    }

    public function getDonnees(): array
    {
        return $this->donnees;
    }

    public function setDonnees(array $donnees): static
    {
        $this->donnees = $donnees;

        return $this;
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): static
    {
        $this->remarques = $remarques;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getValidatedAt(): ?DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?DateTimeInterface $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    /** @return Collection<int, DepouillementHistory> */
    public function getHistories(): Collection
    {
        return $this->histories;
    }

    /** @return Collection<int, Conception> */
    public function getConceptions(): Collection
    {
        return $this->conceptions;
    }

    public function addConception(Conception $conception): static
    {
        if (!$this->conceptions->contains($conception)) {
            $this->conceptions->add($conception);
            $conception->setDepouillement($this);
        }

        return $this;
    }

    public function removeConception(Conception $conception): static
    {
        $this->conceptions->removeElement($conception);

        return $this;
    }

    /** @return Collection<int, DepouillementEtiquette> */
    public function getEtiquettes(): Collection
    {
        return $this->etiquettes;
    }

    public function addEtiquette(DepouillementEtiquette $etiquette): static
    {
        if (!$this->etiquettes->contains($etiquette)) {
            $this->etiquettes->add($etiquette);
            $etiquette->setDepouillement($this);
        }

        return $this;
    }

    public function removeEtiquette(DepouillementEtiquette $etiquette): static
    {
        $this->etiquettes->removeElement($etiquette);

        return $this;
    }

    /** @return Collection<int, DepouillementPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(DepouillementPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setDepouillement($this);
        }

        return $this;
    }

    public function removePhoto(DepouillementPhoto $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    public function addHistory(DepouillementHistory $history): static
    {
        if (!$this->histories->contains($history)) {
            $this->histories->add($history);
            $history->setDepouillement($this);
        }

        return $this;
    }
}
