<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[ORM\Table(name: 'intrigue_has_modification')]
#[ORM\Index(columns: ['intrigue_id'], name: 'fk_intrigue_has_modification_intrigue1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_intrigue_has_modification_User1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseIntrigueHasModification', 'extended' => 'IntrigueHasModification'])]
abstract class BaseIntrigueHasModification
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`date`", type="datetime")
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    protected \DateTime $date;

    #[ORM\ManyToOne(targetEntity: Intrigue::class, cascade: ['persist', 'remove'], inversedBy: 'intrigueHasModifications')]
    #[ORM\JoinColumn(name: 'intrigue_id', referencedColumnName: 'id', nullable: false)]
    protected Intrigue $intrigue;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'intrigueHasModifications')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

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
     * Set the value of date.
     */
    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set Intrigue entity (many to one).
     */
    public function setIntrigue(Intrigue $intrigue = null): static
    {
        $this->intrigue = $intrigue;

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
     * Set User entity (many to one).
     */
    public function setUser(User $User = null): static
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /* public function __sleep()
    {
        return ['id', 'date', 'intrigue_id', 'User_id'];
    } */
}
