<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiClientRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Application tierce autorisée à utiliser l'API (app compagnon, etc.).
 *
 * Le secret n'est jamais stocké en clair : seule son empreinte SHA-256 l'est (le secret est
 * un aléa de 256 bits, une empreinte rapide suffit). Les droits de l'application sont ceux
 * du compte de service associé.
 */
#[ORM\Entity(repositoryClass: ApiClientRepository::class)]
#[ORM\Table(name: 'api_client')]
class ApiClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $name;

    #[ORM\Column(name: 'client_id', type: Types::STRING, length: 40, unique: true)]
    private string $clientId;

    #[ORM\Column(name: 'secret_hash', type: Types::STRING, length: 64)]
    private string $secretHash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, options: ['unsigned' => true])]
    private User $user;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_used_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(string $name, string $clientId, string $secretHash, User $user)
    {
        $this->name = $name;
        $this->clientId = $clientId;
        $this->secretHash = $secretHash;
        $this->user = $user;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function markUsed(): static
    {
        $this->lastUsedAt = new DateTimeImmutable();

        return $this;
    }
}
