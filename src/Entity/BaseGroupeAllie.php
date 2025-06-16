<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'groupe_allie')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_allie_groupe1_idx')]
#[ORM\Index(columns: ['groupe_allie_id'], name: 'fk_groupe_allie_groupe2_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeAllie', 'extended' => 'GroupeAllie'])]
abstract class BaseGroupeAllie
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $groupe_accepted = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $groupe_allie_accepted = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $message = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $message_allie = null;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeAllieRelatedByGroupeIds')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupeRelatedByGroupeId;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeAllieRelatedByGroupeAllieIds')]
    #[JoinColumn(name: 'groupe_allie_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupeRelatedByGroupeAllieId;

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
     * Set the value of groupe_accepted.
     */
    public function setGroupeAccepted(bool $groupe_accepted): static
    {
        $this->groupe_accepted = $groupe_accepted;

        return $this;
    }

    /**
     * Get the value of groupe_accepted.
     */
    public function getGroupeAccepted(): bool
    {
        return $this->groupe_accepted;
    }

    /**
     * Set the value of groupe_allie_accepted.
     *
     * @param bool $groupe_allie_accepted
     */
    public function setGroupeAllieAccepted(GroupeAllie $groupe_allie_accepted): static
    {
        $this->groupe_allie_accepted = $groupe_allie_accepted;

        return $this;
    }

    /**
     * Get the value of groupe_allie_accepted.
     */
    public function getGroupeAllieAccepted(): bool
    {
        return $this->groupe_allie_accepted;
    }

    /**
     * Set the value of message.
     *
     * @param string $message
     */
    public function setMessage(GroupeAllie $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of message.
     */
    public function getMessage(): string
    {
        return $this->message ?? '';
    }

    /**
     * Set the value of message_allie.
     */
    public function setMessageAllie(string $message_allie): static
    {
        $this->message_allie = $message_allie;

        return $this;
    }

    /**
     * Get the value of message_allie.
     */
    public function getMessageAllie(): string
    {
        return $this->message_allie ?? '';
    }

    /**
     * Set Groupe entity related by `groupe_id` (many to one).
     */
    public function setGroupeRelatedByGroupeId(Groupe $groupe = null): static
    {
        $this->groupeRelatedByGroupeId = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity related by `groupe_id` (many to one).
     */
    public function getGroupeRelatedByGroupeId(): Groupe
    {
        return $this->groupeRelatedByGroupeId;
    }

    /**
     * Set Groupe entity related by `groupe_allie_id` (many to one).
     */
    public function setGroupeRelatedByGroupeAllieId(Groupe $groupe = null): static
    {
        $this->groupeRelatedByGroupeAllieId = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity related by `groupe_allie_id` (many to one).
     */
    public function getGroupeRelatedByGroupeAllieId(): Groupe
    {
        return $this->groupeRelatedByGroupeAllieId;
    }

    /* public function __sleep()
    {
        return ['id', 'groupe_id', 'groupe_allie_id', 'groupe_accepted', 'groupe_allie_accepted', 'message', 'message_allie'];
    } */
}
