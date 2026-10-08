<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use App\Repository\BilletSyncRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Participant HelloAsso Plus Billetterie lu lors d'une synchronisation, avec sa proposition de rapprochement.
 */
#[ORM\Entity(repositoryClass: BilletSyncRepository::class)]
#[ORM\Table(name: 'billet_sync')]
#[ORM\UniqueConstraint(name: 'uniq_billet_sync_attendee', columns: ['gn_id', 'attendee_id'])]
class BilletSync
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Gn $gn = null;

    #[ORM\Column(name: 'attendee_id', type: Types::STRING, length: 64)]
    private string $attendeeId = '';

    #[ORM\Column(name: 'order_id', type: Types::STRING, length: 64, nullable: true)]
    private ?string $orderId = null;

    #[ORM\Column(name: 'product_id', type: Types::STRING, length: 64, nullable: true)]
    private ?string $productId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $nom = null;

    /** Statut HelloAsso : enabled, pending, disabled, used, resold, refunded. */
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $price = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Billet::class)]
    #[ORM\JoinColumn(name: 'billet_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Billet $billet = null;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: SyncConfiance::class)]
    private SyncConfiance $confiance = SyncConfiance::AUCUN;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: SyncEtat::class)]
    private SyncEtat $etat = SyncEtat::A_VALIDER;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'raw_data', type: Types::JSON, nullable: true)]
    private ?array $rawData = null;

    #[ORM\Column(name: 'synchronise_le', type: Types::DATETIME_MUTABLE)]
    private DateTime $synchroniseLe;

    #[ORM\Column(name: 'valide_le', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $valideLe = null;

    public function __construct()
    {
        $this->synchroniseLe = new DateTime();
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

    public function getAttendeeId(): string
    {
        return $this->attendeeId;
    }

    public function setAttendeeId(string $attendeeId): static
    {
        $this->attendeeId = $attendeeId;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** Montant en centimes. */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

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

    public function getBillet(): ?Billet
    {
        return $this->billet;
    }

    public function setBillet(?Billet $billet): static
    {
        $this->billet = $billet;

        return $this;
    }

    public function getConfiance(): SyncConfiance
    {
        return $this->confiance;
    }

    public function setConfiance(SyncConfiance $confiance): static
    {
        $this->confiance = $confiance;

        return $this;
    }

    public function getEtat(): SyncEtat
    {
        return $this->etat;
    }

    public function setEtat(SyncEtat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /** @param array<string, mixed>|null $rawData */
    public function setRawData(?array $rawData): static
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function getSynchroniseLe(): DateTime
    {
        return $this->synchroniseLe;
    }

    public function setSynchroniseLe(DateTime $synchroniseLe): static
    {
        $this->synchroniseLe = $synchroniseLe;

        return $this;
    }

    public function getValideLe(): ?DateTime
    {
        return $this->valideLe;
    }

    public function setValideLe(?DateTime $valideLe): static
    {
        $this->valideLe = $valideLe;

        return $this;
    }

    /** Le participant HelloAsso a payé et n'est ni remboursé ni désactivé. */
    public function isPaye(): bool
    {
        return \in_array($this->status, ['enabled', 'used'], true);
    }
}
