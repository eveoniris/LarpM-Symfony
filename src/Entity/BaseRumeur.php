<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'rumeur')]
#[ORM\Index(columns: ['territoire_id'], name: 'fk_rumeur_territoire1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_rumeur_user1_idx')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_rumeur_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRumeur', 'extended' => 'Rumeur'])]
abstract class BaseRumeur
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, name: 'text')]
    protected string $text = '';

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTime $update_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected $visibility = '';

    #[ManyToOne(targetEntity: Gn::class, inversedBy: 'rumeurs')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: false)]
    protected Gn $gn;

    #[ManyToOne(targetEntity: Territoire::class, inversedBy: 'rumeurs')]
    #[JoinColumn(name: 'territoire_id', referencedColumnName: 'id', nullable: false)]
    protected Territoire $territoire;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'rumeurs')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $user = null;

    public function __construct()
    {
        $this->creation_date = new \DateTime('now');
        $this->update_date = new \DateTime('now');
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
     */
    public function setCreationDate(\DateTime $creation_date): static
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
     * Set the value of visibility.
     */
    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get the value of visibility.
     */
    public function getVisibility(): string
    {
        return $this->visibility ?? '';
    }

    /**
     * Set Gn entity (many to one).
     */
    public function setGn(?Gn $gn = null): static
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /**
     * Set Territoire entity (many to one).
     */
    public function setTerritoire(?Territoire $territoire = null): static
    {
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (many to one).
     */
    public function getTerritoire(): Territoire
    {
        return $this->territoire;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(?User $user = null): static
    {
        $this->user = $user;

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
        return ['id', 'text', 'gn_id', 'territoire_id', 'User_id', 'creation_date', 'update_date', 'visibility'];
    } */
}
