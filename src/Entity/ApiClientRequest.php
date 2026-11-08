<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiClientRequestRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Demande de création d'une application tierce, faite par un utilisateur et traitée par un admin.
 * Une demande ne porte que sur une application en lecture seule (ROLE_API_READ).
 */
#[ORM\Entity(repositoryClass: ApiClientRequestRepository::class)]
#[ORM\Table(name: 'api_client_request')]
class ApiClientRequest
{
    public const string EN_ATTENTE = 'en_attente';
    public const string ACCEPTEE = 'acceptee';
    public const string REFUSEE = 'refusee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requester_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE', options: ['unsigned' => true])]
    private User $requester;

    /** Nom d'application souhaité (même format que ApiClient::name). */
    #[ORM\Column(type: Types::STRING, length: 60)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $purpose;

    #[ORM\ManyToOne(targetEntity: Gn::class)]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Gn $gn = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::EN_ATTENTE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'decided_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $decidedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'decided_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['unsigned' => true])]
    private ?User $decidedBy = null;

    #[ORM\Column(name: 'decision_note', type: Types::TEXT, nullable: true)]
    private ?string $decisionNote = null;

    public function __construct(User $requester, string $name, string $purpose, ?Gn $gn)
    {
        $this->requester = $requester;
        $this->name = $name;
        $this->purpose = $purpose;
        $this->gn = $gn;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): User
    {
        return $this->requester;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return self::EN_ATTENTE === $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDecidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function getDecidedBy(): ?User
    {
        return $this->decidedBy;
    }

    public function getDecisionNote(): ?string
    {
        return $this->decisionNote;
    }

    public function accept(User $admin): void
    {
        $this->decide(self::ACCEPTEE, $admin, null);
    }

    public function refuse(User $admin, ?string $note): void
    {
        $this->decide(self::REFUSEE, $admin, $note);
    }

    private function decide(string $status, User $admin, ?string $note): void
    {
        $this->status = $status;
        $this->decidedBy = $admin;
        $this->decidedAt = new DateTimeImmutable();
        $this->decisionNote = $note;
    }
}
