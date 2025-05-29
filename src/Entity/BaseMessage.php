<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[ORM\Table(name: 'message')]
#[ORM\Index(columns: ['auteur'], name: 'fk_message_user1_idx')]
#[ORM\Index(columns: ['destinataire'], name: 'fk_message_user2_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseMessage', 'extended' => 'Message'])]
class BaseMessage
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Assert\Length(45)]
    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $title = null;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $text = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[Column(type: Types::BOOLEAN)]
    protected ?bool $lu = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messageRelatedByAuteurs')]
    #[ORM\JoinColumn(name: 'auteur', referencedColumnName: 'id', nullable: false)]
    protected User $userRelatedByAuteur;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messageRelatedByDestinataires')]
    #[ORM\JoinColumn(name: 'destinataire', referencedColumnName: 'id', nullable: false)]
    protected User $userRelatedByDestinataire;

    public function __construct()
    {
        $this->creation_date = new \DateTime('now');
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of creation_date.
     */
    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of lu.
     */
    public function getLu(): bool
    {
        return $this->lu ?? false;
    }

    /**
     * Set the value of lu.
     */
    public function setLu(bool $lu): static
    {
        $this->lu = $lu;

        return $this;
    }

    /**
     * Get the value of text.
     */
    public function getText(): string
    {
        return $this->text ?? '';
    }

    /**
     * Set the value of text.
     */
    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of title.
     */
    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * Set the value of title.
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of update_date.
     */
    public function getUpdateDate(): \DateTime
    {
        return $this->update_date;
    }

    /**
     * Set the value of update_date.
     */
    public function setUpdateDate(\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get User entity related by `auteur` (many to one).
     */
    public function getUserRelatedByAuteur(): ?User
    {
        return $this->userRelatedByAuteur;
    }

    public function setUserRelatedByAuteur(?User $User = null): static
    {
        $this->userRelatedByAuteur = $User;

        return $this;
    }

    public function getUserRelatedByDestinataire(): ?User
    {
        return $this->userRelatedByDestinataire;
    }

    public function setUserRelatedByDestinataire(?User $user = null): static
    {
        $this->userRelatedByDestinataire = $user;

        return $this;
    }
}
