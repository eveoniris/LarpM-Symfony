<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LogActionType;
use App\Repository\LogActionRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: LogActionRepository::class)]
class LogAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'logActions')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    /** Adresse IP de la requête à l'origine de l'action (IPv4 ou IPv6), null pour une action système. */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'user_agent', length: 512, nullable: true)]
    private ?string $userAgent = null;

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = null === $userAgent ? null : mb_substr($userAgent, start: 0, length: 512);

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getData(): ?array
    {
        return $this->data;
    }

    /** @param array<string, mixed>|null $data */
    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getDataAsJson(): ?string
    {
        return json_encode($this->data, \JSON_THROW_ON_ERROR);
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?LogActionType
    {
        return LogActionType::tryFrom($this->type);
    }

    /** Valeur brute en base, y compris les types absents de l'enum. */
    public function getRawType(): ?string
    {
        return $this->type;
    }

    public function setType(string|LogActionType|null $type): static
    {
        if ($type instanceof LogActionType) {
            $this->type = $type->value;

            return $this;
        }

        if (null === $type) {
            $this->type = null;

            return $this;
        }

        $this->type = LogActionType::OTHER->value;

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
}
