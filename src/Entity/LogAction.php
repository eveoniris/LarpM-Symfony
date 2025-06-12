<?php

namespace App\Entity;

use App\Enum\LogActionType;
use App\Helper\DataFormatter;
use App\Repository\LogActionRepository;
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
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'true')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getDataAsJson(): ?string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
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
