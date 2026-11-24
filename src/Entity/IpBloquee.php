<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IpBloqueeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Adresse IP (ou plage CIDR) bloquée par un administrateur sur les points d'entrée sensibles
 * (connexion, mot de passe oublié, inscription). Le blocage expire toujours (1 an maximum).
 */
#[ORM\Entity(repositoryClass: IpBloqueeRepository::class)]
#[ORM\Table(name: 'ip_bloquee')]
#[ORM\UniqueConstraint(name: 'uniq_ip_bloquee_cidr', columns: ['cidr'])]
#[ORM\Index(name: 'idx_ip_bloquee_expires', columns: ['expires_at'])]
class IpBloquee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    /** Adresse normalisée avec son préfixe : « 1.2.3.4/32 » ou « 2001:db8:0:1::/64 ». */
    #[ORM\Column(length: 49)]
    private string $cidr;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', options: ['unsigned' => true])]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    public function __construct(string $cidr, DateTimeImmutable $expiresAt, ?DateTimeImmutable $createdAt = null)
    {
        $this->cidr = $cidr;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCidr(): string
    {
        return $this->cidr;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isActive(?DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt > ($now ?? new DateTimeImmutable());
    }
}
