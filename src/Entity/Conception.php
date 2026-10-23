<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ConceptionIssue;
use App\Enum\ConceptionStatut;
use App\Repository\ConceptionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conception d'un héritier, signalée dans un retour de jeu : les deux parents, le tirage (d20) et l'issue suggérée.
 *
 * Le scénariste décide ensuite : créer le ou les héritiers, ou gérer la création lui-même plus tard (choix consigné).
 * Une conception venant d'une simulation (`simulation` = true) ne crée jamais d'héritier.
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: ConceptionRepository::class)]
#[ORM\Table(name: 'conception')]
#[ORM\Index(columns: ['gn_id', 'statut'], name: 'idx_conception_gn_statut')]
class Conception
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Gn $gn;

    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'parent1_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Personnage $parent1;

    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'parent2_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Personnage $parent2;

    /** Dépouillement dans lequel la conception a été signalée (elle disparaît avec lui, par exemple à la purge d'une simulation). */
    #[ORM\ManyToOne(targetEntity: Depouillement::class, inversedBy: 'conceptions')]
    #[ORM\JoinColumn(name: 'depouillement_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Depouillement $depouillement = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $simulation = false;

    /** Résultat du d20, null quand un parent ne peut pas concevoir (âge). Tiré une seule fois, jamais re-tiré. */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $tirage = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true, enumType: ConceptionIssue::class)]
    private ?ConceptionIssue $issue = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ConceptionStatut::class)]
    private ConceptionStatut $statut = ConceptionStatut::EN_ATTENTE;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $anomalies = [];

    /** Raison ou note du scénariste (par exemple pourquoi il gère la création plus tard). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarque = null;

    /** @var list<int> identifiants des personnages héritiers créés */
    #[ORM\Column(type: Types::JSON)]
    private array $heritiers = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'cree_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'decide_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $decidePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $decideAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
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

    public function getParent1(): Personnage
    {
        return $this->parent1;
    }

    public function setParent1(Personnage $parent1): static
    {
        $this->parent1 = $parent1;

        return $this;
    }

    public function getParent2(): Personnage
    {
        return $this->parent2;
    }

    public function setParent2(Personnage $parent2): static
    {
        $this->parent2 = $parent2;

        return $this;
    }

    public function getDepouillement(): ?Depouillement
    {
        return $this->depouillement;
    }

    public function setDepouillement(?Depouillement $depouillement): static
    {
        $this->depouillement = $depouillement;

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

    public function getTirage(): ?int
    {
        return $this->tirage;
    }

    public function setTirage(?int $tirage): static
    {
        $this->tirage = $tirage;

        return $this;
    }

    public function getIssue(): ?ConceptionIssue
    {
        return $this->issue;
    }

    public function setIssue(?ConceptionIssue $issue): static
    {
        $this->issue = $issue;

        return $this;
    }

    public function getStatut(): ConceptionStatut
    {
        return $this->statut;
    }

    public function setStatut(ConceptionStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /** @return list<string> */
    public function getAnomalies(): array
    {
        return $this->anomalies;
    }

    /** @param list<string> $anomalies */
    public function setAnomalies(array $anomalies): static
    {
        $this->anomalies = $anomalies;

        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): static
    {
        $this->remarque = $remarque;

        return $this;
    }

    /** @return list<int> */
    public function getHeritiers(): array
    {
        return $this->heritiers;
    }

    /** @param list<int> $heritiers */
    public function setHeritiers(array $heritiers): static
    {
        $this->heritiers = $heritiers;

        return $this;
    }

    public function getCreePar(): ?User
    {
        return $this->creePar;
    }

    public function setCreePar(?User $creePar): static
    {
        $this->creePar = $creePar;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDecidePar(): ?User
    {
        return $this->decidePar;
    }

    public function setDecidePar(?User $decidePar): static
    {
        $this->decidePar = $decidePar;

        return $this;
    }

    public function getDecideAt(): ?DateTimeInterface
    {
        return $this->decideAt;
    }

    public function setDecideAt(?DateTimeInterface $decideAt): static
    {
        $this->decideAt = $decideAt;

        return $this;
    }

    /** Le parent est l'un des deux parents de cette conception. */
    public function aPourParent(Personnage $personnage): bool
    {
        return $this->parent1->getId() === $personnage->getId() || $this->parent2->getId() === $personnage->getId();
    }
}
