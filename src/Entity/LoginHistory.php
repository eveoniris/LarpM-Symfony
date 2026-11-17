<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LoginMethod;
use App\Repository\LoginHistoryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace d'une tentative de connexion (réussie ou échouée).
 *
 * Donnée de sécurité à durée de conservation limitée (voir la commande de purge) :
 * l'IP est anonymisée après quelques jours, la ligne supprimée après 12 mois.
 */
// @mago-expect lint:too-many-properties
#[ORM\Entity(repositoryClass: LoginHistoryRepository::class)]
#[ORM\Table(name: 'login_history')]
#[ORM\Index(name: 'idx_login_history_user_date', columns: ['user_id', 'date'])]
#[ORM\Index(name: 'idx_login_history_ip_date', columns: ['ip', 'date'])]
#[ORM\Index(name: 'idx_login_history_date', columns: ['date'])]
class LoginHistory
{
    public const int USER_AGENT_MAX_LENGTH = 512;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    /** Null si l'identifiant saisi ne correspond à aucun compte, ou si le compte a été supprimé. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['unsigned' => true])]
    private ?User $user = null;

    /** Identifiant saisi (email), utile pour les échecs sur compte inconnu. */
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $identifier = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $date;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $success;

    #[ORM\Column(length: 20, enumType: LoginMethod::class)]
    private LoginMethod $method;

    #[ORM\Column(name: 'failure_reason', length: 100, nullable: true)]
    private ?string $failureReason = null;

    /** IPv4 ou IPv6, dernier octet à 0 une fois anonymisée. */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    /** Passe à true quand l'IP a été anonymisée par la commande de purge. */
    #[ORM\Column(name: 'ip_anonymized', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $ipAnonymized = false;

    #[ORM\Column(name: 'user_agent', length: self::USER_AGENT_MAX_LENGTH, nullable: true)]
    private ?string $userAgent = null;

    /** Empreinte serveur (SHA-256 salé de l'User-Agent et de la langue), pas un identifiant d'appareil. */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $fingerprint = null;

    public function __construct(LoginMethod $method, bool $success, ?DateTimeImmutable $date = null)
    {
        $this->method = $method;
        $this->success = $success;
        $this->date = $date ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): static
    {
        $this->identifier = null === $identifier ? null : mb_substr($identifier, start: 0, length: 180);

        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMethod(): LoginMethod
    {
        return $this->method;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = null === $failureReason ? null : mb_substr($failureReason, start: 0, length: 100);

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function isIpAnonymized(): bool
    {
        return $this->ipAnonymized;
    }

    public function setIpAnonymized(bool $ipAnonymized): static
    {
        $this->ipAnonymized = $ipAnonymized;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = null === $userAgent ? null : mb_substr($userAgent, start: 0, length: self::USER_AGENT_MAX_LENGTH);

        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }
}
