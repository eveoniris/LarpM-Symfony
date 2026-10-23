<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DepouillementStatut;
use App\Repository\DepouillementHistoryRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Qui, quand et quoi pour un dépouillement : changements de statut, prises en charge successives, modifications de saisie.
 */
#[ORM\Entity(repositoryClass: DepouillementHistoryRepository::class)]
#[ORM\Table(name: 'depouillement_history')]
#[ORM\Index(columns: ['depouillement_id'], name: 'idx_depouillement_history_depouillement')]
class DepouillementHistory
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_MISE_A_JOUR = 'MISE_A_JOUR';
    public const ACTION_STATUT = 'STATUT';
    public const ACTION_REPRISE = 'REPRISE';
    public const ACTION_VALIDATION = 'VALIDATION';

    public const ACTIONS = [
        self::ACTION_CREATE => 'Création',
        self::ACTION_MISE_A_JOUR => 'Mise à jour',
        self::ACTION_STATUT => 'Changement de statut',
        self::ACTION_REPRISE => 'Reprise du dépouillement',
        self::ACTION_VALIDATION => 'Validation',
    ];

    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Depouillement::class, inversedBy: 'histories')]
    #[ORM\JoinColumn(name: 'depouillement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Depouillement $depouillement;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $actionDate;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $actionType;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: DepouillementStatut::class)]
    private ?DepouillementStatut $statutAvant = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: DepouillementStatut::class)]
    private ?DepouillementStatut $statutApres = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dataBefore = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $dataAfter = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    public function __construct()
    {
        $this->actionDate = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepouillement(): Depouillement
    {
        return $this->depouillement;
    }

    public function setDepouillement(Depouillement $depouillement): static
    {
        $this->depouillement = $depouillement;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getActionDate(): DateTimeInterface
    {
        return $this->actionDate;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getStatutAvant(): ?DepouillementStatut
    {
        return $this->statutAvant;
    }

    public function setStatutAvant(?DepouillementStatut $statutAvant): static
    {
        $this->statutAvant = $statutAvant;

        return $this;
    }

    public function getStatutApres(): ?DepouillementStatut
    {
        return $this->statutApres;
    }

    public function setStatutApres(?DepouillementStatut $statutApres): static
    {
        $this->statutApres = $statutApres;

        return $this;
    }

    public function getDataBefore(): ?array
    {
        return $this->dataBefore;
    }

    public function setDataBefore(?array $dataBefore): static
    {
        $this->dataBefore = $dataBefore;

        return $this;
    }

    public function getDataAfter(): ?array
    {
        return $this->dataAfter;
    }

    public function setDataAfter(?array $dataAfter): static
    {
        $this->dataAfter = $dataAfter;

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
}
