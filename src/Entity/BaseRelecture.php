<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[ORM\Table(name: 'relecture')]
#[ORM\Index(columns: ['user_id'], name: 'fk_relecture_user1_idx')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_relecture_intrigue1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRelecture', 'extended' => 'Relecture'])]
class BaseRelecture
{
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`date`", type="datetime")
     */
    #[Column(name: 'date', type: Types::DATETIME_MUTABLE)]
    protected \DateTime $date;

    /**
     * @Column(type="string", length=45)
     */
    #[Column(type: Types::STRING, length: 45)]
    protected string $statut;

    /**
     * @Column(type="text", nullable=true)
     */
    #[Column(type: Types::TEXT, nullable: 45)]
    protected string $remarque = '';

    #[ManyToOne(targetEntity: User::class, inversedBy: 'relectures')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    #[ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'relectures')]
    #[JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    public function __construct()
    {
    }

    /**
     * Get the value of date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set the value of date.
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

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
     * Get Intrigue entity (many to one).
     */
    public function getIntrigue(): Intrigue
    {
        return $this->intrigue;
    }

    /**
     * Set Intrigue entity (many to one).
     */
    public function setIntrigue(?Intrigue $intrigue = null): static
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get the value of remarque.
     */
    public function getRemarque(): string
    {
        return $this->remarque ?? '';
    }

    /**
     * Set the value of remarque.
     */
    public function setRemarque(?string $remarque): static
    {
        $this->remarque = $remarque ?? '';

        return $this;
    }

    /**
     * Get the value of statut.
     */
    public function getStatut(): string
    {
        return $this->statut;
    }

    /**
     * Set the value of statut.
     */
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    /* public function __sleep()
    {
        return ['id', 'date', 'statut', 'remarque', 'User_id', 'intrigue_id'];
    } */
}
