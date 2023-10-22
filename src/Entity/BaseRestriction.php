<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'restriction')]
#[ORM\Index(columns: ['auteur_id'], name: 'fk_restriction_user1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseRestriction', 'extended' => 'Restriction'])]
class BaseRestriction
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected int $id;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 90)]
    protected string $label;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'restrictionRelatedByAuteurIds')]
    #[JoinColumn(name: 'auteur_id', referencedColumnName: 'id', nullable: 'false')]
    protected User $userRelatedByAuteurId;

    #[ManyToMany(targetEntity: User::class, mappedBy: 'restrictions')]
    protected ArrayCollection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    /**
     * Set User entity related by `auteur_id` (many to one).
     *
     * @return \App\Entity\Restriction
     */
    public function setUserRelatedByAuteurId(User $user = null): static
    {
        $this->userRelatedByAuteurId = $user;

        return $this;
    }

    /**
     * Get User entity related by `auteur_id` (many to one).
     *
     * @return \App\Entity\User
     */
    public function getUserRelatedByAuteurId(): User
    {
        return $this->userRelatedByAuteurId;
    }

    public function addUser(User $user): static
    {
        $this->users->add($user);

        return $this;
    }

    public function removeUser(User $User): static
    {
        $this->users->removeElement($User);

        return $this;
    }

    public function getUsers(): ArrayCollection
    {
        return $this->users;
    }

    public function __sleep()
    {
        return ['id', 'label', 'creation_date', 'update_date', 'auteur_id'];
    }
}
