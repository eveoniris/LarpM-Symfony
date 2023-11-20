<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[ORM\Table(name: 'message')]
#[ORM\Index(columns: ['auteur'], name: 'fk_message_user1_idx')]
#[ORM\Index(columns: ['personnage_secondaire_id'], name: 'fk_user_personnage_secondaire1_idx')]
#[ORM\Index(columns: ['destinataire'], name: 'fk_message_user2_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseMessage', 'extended' => 'Message'])]
class BaseMessage
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $title = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $text = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $lu = false;

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
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

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
     * Set the value of title.
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

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
     * Set the value of text.
     */
    public function setText(string $text): static
    {
        $this->text = $text;

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
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     */
    public function setCreationDate(string $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
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
     * Get the value of update_date.
     */
    public function getUpdateDate(): \DateTime
    {
        return $this->update_date;
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
     * Get the value of lu.
     */
    public function getLu(): bool
    {
        return $this->lu;
    }

    public function setUserRelatedByAuteur(User $User = null): static
    {
        $this->userRelatedByAuteur = $User;

        return $this;
    }

    /**
     * Get User entity related by `auteur` (many to one).
     */
    public function getUserRelatedByAuteur(): ?User
    {
        return $this->userRelatedByAuteur;
    }

    public function setUserRelatedByDestinataire(User $User = null): static
    {
        $this->userRelatedByDestinataire = $User;

        return $this;
    }

    public function getUserRelatedByDestinataire(): ?User
    {
        return $this->userRelatedByDestinataire;
    }

    public function __sleep()
    {
        return ['id', 'title', 'text', 'creation_date', 'update_date', 'lu', 'auteur', 'destinataire'];
    }
}
