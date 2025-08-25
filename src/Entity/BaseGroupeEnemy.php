<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'groupe_enemy')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_enemy_groupe1_idx')]
#[ORM\Index(columns: ['groupe_enemy_id'], name: 'fk_groupe_enemy_groupe2_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeEnemy', 'extended' => 'GroupeEnemy'])]
abstract class BaseGroupeEnemy
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $groupe_peace;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $groupe_enemy_peace;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $message = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $message_enemy = null;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeEnemyRelatedByGroupeIds')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupeRelatedByGroupeId;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeEnemyRelatedByGroupeEnemyIds')]
    #[JoinColumn(name: 'groupe_enemy_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupeRelatedByGroupeEnemyId;

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
     * Set the value of groupe_peace.
     */
    public function setGroupePeace(bool $groupe_peace): static
    {
        $this->groupe_peace = $groupe_peace;

        return $this;
    }

    /**
     * Get the value of groupe_peace.
     */
    public function getGroupePeace(): bool
    {
        return $this->groupe_peace;
    }

    /**
     * Set the value of groupe_enemy_peace.
     */
    public function setGroupeEnemyPeace(bool $groupe_enemy_peace): static
    {
        $this->groupe_enemy_peace = $groupe_enemy_peace;

        return $this;
    }

    /**
     * Get the value of groupe_enemy_peace.
     */
    public function getGroupeEnemyPeace(): bool
    {
        return $this->groupe_enemy_peace;
    }

    /**
     * Set the value of message.
     */
    public function setMessage(string $message): GroupeEnemy
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message_enemy.
     */
    public function setMessageEnemy(string $message_enemy): GroupeEnemy
    {
        $this->message_enemy = $message_enemy;

        return $this;
    }

    /**
     * Get the value of message_enemy.
     */
    public function getMessageEnemy(): string
    {
        return $this->message_enemy;
    }

    /**
     * Set Groupe entity related by `groupe_id` (many to one).
     */
    public function setGroupeRelatedByGroupeId(?Groupe $groupe = null): static
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
     * Set Groupe entity related by `groupe_enemy_id` (many to one).
     */
    public function setGroupeRelatedByGroupeEnemyId(?Groupe $groupe = null): static
    {
        $this->groupeRelatedByGroupeEnemyId = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity related by `groupe_enemy_id` (many to one).
     */
    public function getGroupeRelatedByGroupeEnemyId(): Groupe
    {
        return $this->groupeRelatedByGroupeEnemyId;
    }

    /* public function __sleep()
    {
        return ['id', 'groupe_id', 'groupe_enemy_id', 'groupe_peace', 'groupe_enemy_peace', 'message', 'message_enemy'];
    } */
}
