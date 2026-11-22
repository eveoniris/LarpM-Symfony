<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GnTimelineStatut;
use App\Enum\GnTimelineType;
use App\Enum\GnTimelineVisibilite;
use App\Repository\GnTimelineStepRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Étape de la chronologie d'un GN (jalon d'information ou, plus tard, action planifiée).
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: GnTimelineStepRepository::class)]
#[ORM\Table(name: 'gn_timeline_step')]
#[ORM\Index(name: 'idx_gn_timeline_step_date', columns: ['date_prevue'])]
class GnTimelineStep
{
    /** Valeurs par défaut de la commande app:vieillir-personnages. */
    public const int ANNEES_DEFAUT = 5;

    public const int AGE_MORT_DEFAUT = 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Gn $gn = null;

    #[ORM\Column(length: 150)]
    private string $libelle = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_prevue', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $datePrevue;

    #[ORM\Column(length: 20, enumType: GnTimelineVisibilite::class)]
    private GnTimelineVisibilite $visibilite = GnTimelineVisibilite::PUBLIC;

    #[ORM\Column(length: 30, enumType: GnTimelineType::class, options: ['default' => 'info'])]
    private GnTimelineType $type = GnTimelineType::INFO;

    /** Le cron exécute l'étape quand elle est échue (refusé pour les types non autorisés en automatique). */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $auto = false;

    #[ORM\Column(length: 20, enumType: GnTimelineStatut::class, options: ['default' => 'a_faire'])]
    private GnTimelineStatut $statut = GnTimelineStatut::A_FAIRE;

    #[ORM\Column(name: 'executed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $executedAt = null;

    /** Compte rendu de la dernière exécution (succès ou message d'erreur). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultat = null;

    /** Paramètre numérique de l'action (XP à donner pour XP_PARTICIPATION). */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $xp = null;

    /** Années ajoutées à l'âge des personnages (VIEILLISSEMENT, 5 par défaut). */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $annees = null;

    /** Âge à partir duquel un personnage peut mourir de vieillesse (VIEILLISSEMENT, 60 par défaut). */
    #[ORM\Column(name: 'age_mort', type: Types::INTEGER, nullable: true)]
    private ?int $ageMort = null;

    public function __construct()
    {
        $this->datePrevue = new DateTimeImmutable();
    }

    /**
     * Date retenue pour l'étape : pour un verrouillage, la date de verrouillage du GN (null si le GN n'en a pas, l'étape est alors
     * manuelle) ; sinon la date prévue saisie.
     */
    public function getDateEffective(): ?DateTimeImmutable
    {
        if (GnTimelineType::VERROUILLAGE === $this->type) {
            $date = $this->gn?->getDateVerrouillage();

            return null === $date ? null : DateTimeImmutable::createFromInterface($date);
        }

        return $this->datePrevue;
    }

    public function getAnnees(): ?int
    {
        return $this->annees;
    }

    public function setAnnees(?int $annees): static
    {
        $this->annees = $annees;

        return $this;
    }

    public function getAgeMort(): ?int
    {
        return $this->ageMort;
    }

    public function setAgeMort(?int $ageMort): static
    {
        $this->ageMort = $ageMort;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function setGn(?Gn $gn): static
    {
        $this->gn = $gn;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDatePrevue(): ?DateTimeImmutable
    {
        return $this->datePrevue;
    }

    public function setDatePrevue(?DateTimeImmutable $datePrevue): static
    {
        $this->datePrevue = $datePrevue;

        return $this;
    }

    public function getVisibilite(): GnTimelineVisibilite
    {
        return $this->visibilite;
    }

    public function setVisibilite(GnTimelineVisibilite $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getType(): GnTimelineType
    {
        return $this->type;
    }

    public function setType(GnTimelineType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isAuto(): bool
    {
        return $this->auto;
    }

    public function setAuto(bool $auto): static
    {
        $this->auto = $auto;

        return $this;
    }

    public function getStatut(): GnTimelineStatut
    {
        return $this->statut;
    }

    public function setStatut(GnTimelineStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getExecutedAt(): ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function getXp(): ?int
    {
        return $this->xp;
    }

    public function setXp(?int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }
}
