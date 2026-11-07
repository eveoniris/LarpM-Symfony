<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RulebookStatut;
use App\Repository\RulebookRegleRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une règle de gameplay. Deux textes en markdown pur : la version claire (lue par les scénaristes) et le détail
 * technique (formule, edge cases, réservé à l'équipe règle).
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: RulebookRegleRepository::class)]
#[ORM\Table(name: 'rulebook_regle')]
#[ORM\UniqueConstraint(name: 'uniq_rulebook_regle_code', columns: ['code'])]
#[ORM\Index(columns: ['statut'], name: 'idx_rulebook_regle_statut')]
class RulebookRegle
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RulebookDomaine::class, inversedBy: 'regles')]
    #[ORM\JoinColumn(name: 'domaine_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RulebookDomaine $domaine;

    /** Identifiant stable cité par les tests et les liens (ex: "XP-03"). */
    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 200)]
    private string $titre;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    /** Version claire, en markdown, lue par les scénaristes. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texteClair = null;

    /** Formule précise, edge cases, en markdown, réservé à l'équipe règle. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailTechnique = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: RulebookStatut::class)]
    private RulebookStatut $statut = RulebookStatut::A_CONFIRMER;

    /**
     * Tests qui couvrent la règle (chemins ou classes).
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $testsRefs = [];

    /** Une règle non publiée n'est visible que de l'équipe règle. */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $publiee = false;

    /** Numéro de la version courante (incrémenté à chaque enregistrement, sert de lien depuis le journal d'actions). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $version = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    /** @var Collection<int, RulebookRevision> */
    #[ORM\OneToMany(mappedBy: 'regle', targetEntity: RulebookRevision::class, cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['version' => 'DESC'])]
    private Collection $revisions;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->revisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomaine(): RulebookDomaine
    {
        return $this->domaine;
    }

    public function setDomaine(RulebookDomaine $domaine): static
    {
        $this->domaine = $domaine;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTexteClair(): ?string
    {
        return $this->texteClair;
    }

    public function setTexteClair(?string $texteClair): static
    {
        $this->texteClair = $texteClair;

        return $this;
    }

    public function getDetailTechnique(): ?string
    {
        return $this->detailTechnique;
    }

    public function setDetailTechnique(?string $detailTechnique): static
    {
        $this->detailTechnique = $detailTechnique;

        return $this;
    }

    public function getStatut(): RulebookStatut
    {
        return $this->statut;
    }

    public function setStatut(RulebookStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /** @return list<string> */
    public function getTestsRefs(): array
    {
        return $this->testsRefs;
    }

    /** @param list<string> $testsRefs */
    public function setTestsRefs(array $testsRefs): static
    {
        $this->testsRefs = array_values($testsRefs);

        return $this;
    }

    public function isPubliee(): bool
    {
        return $this->publiee;
    }

    public function setPubliee(bool $publiee): static
    {
        $this->publiee = $publiee;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

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

    public function setUpdatedAt(DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /** @return Collection<int, RulebookRevision> */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    /**
     * Visible d'un scénariste : publiée, dans un statut public, et avec une version claire rédigée.
     */
    public function isVisibleScenariste(): bool
    {
        return $this->publiee && $this->statut->isPublic() && null !== $this->texteClair && '' !== trim($this->texteClair);
    }
}
