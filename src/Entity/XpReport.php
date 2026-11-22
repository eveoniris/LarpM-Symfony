<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\XpReportRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Report du bonus d'XP d'un personnage mort vers un autre personnage (règles XP-05 à XP-07).
 *
 * Deux unicités : un mort ne transmet qu'une fois, et un personnage ne reçoit qu'un seul bonus.
 * Un report annulé est supprimé (la trace reste dans log_action).
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: XpReportRepository::class)]
#[ORM\Table(name: 'xp_report')]
#[ORM\UniqueConstraint(name: 'uniq_xp_report_mort', columns: ['personnage_mort_id'])]
#[ORM\UniqueConstraint(name: 'uniq_xp_report_beneficiaire', columns: ['personnage_beneficiaire_id'])]
class XpReport
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'personnage_mort_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Personnage $personnageMort;

    #[ORM\ManyToOne(targetEntity: Personnage::class)]
    #[ORM\JoinColumn(name: 'personnage_beneficiaire_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Personnage $personnageBeneficiaire;

    /** `tiers` ou `moitie` (voir XpReportCalculateur). */
    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $mode;

    /** Base de calcul figée au moment du report. */
    #[ORM\Column(type: Types::INTEGER)]
    private int $xpBase;

    #[ORM\Column(type: Types::INTEGER)]
    private int $xpReporte;

    #[ORM\OneToOne(targetEntity: ExperienceGain::class)]
    #[ORM\JoinColumn(name: 'experience_gain_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ExperienceGain $experienceGain = null;

    /** Vrai si le scénariste a forcé le report malgré une participation déjà passée. */
    #[ORM\Column(name: '`force`', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $force = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'auteur_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $auteur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnageMort(): Personnage
    {
        return $this->personnageMort;
    }

    public function setPersonnageMort(Personnage $personnageMort): static
    {
        $this->personnageMort = $personnageMort;

        return $this;
    }

    public function getPersonnageBeneficiaire(): Personnage
    {
        return $this->personnageBeneficiaire;
    }

    public function setPersonnageBeneficiaire(Personnage $personnageBeneficiaire): static
    {
        $this->personnageBeneficiaire = $personnageBeneficiaire;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getXpBase(): int
    {
        return $this->xpBase;
    }

    public function setXpBase(int $xpBase): static
    {
        $this->xpBase = $xpBase;

        return $this;
    }

    public function getXpReporte(): int
    {
        return $this->xpReporte;
    }

    public function setXpReporte(int $xpReporte): static
    {
        $this->xpReporte = $xpReporte;

        return $this;
    }

    public function getExperienceGain(): ?ExperienceGain
    {
        return $this->experienceGain;
    }

    public function setExperienceGain(?ExperienceGain $experienceGain): static
    {
        $this->experienceGain = $experienceGain;

        return $this;
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function setForce(bool $force): static
    {
        $this->force = $force;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
