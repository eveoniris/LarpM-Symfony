<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_background')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_personnage_background_personnage1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_personnage_background_User1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageBackground', 'extended' => 'PersonnageBackground'])]
abstract class BasePersonnageBackground
{
    #[Id, Column(type: Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $text = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $visibility = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected \DateTime $creation_date;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected $update_date;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageBackgrounds')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'personnageBackgrounds')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $user = null;

    #[ManyToOne(targetEntity: Gn::class, inversedBy: 'personnageBackgrounds')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected $gn;

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
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    /**
     * Set User entity (many to one).
     */
    public function setUser(?User $User = null): static
    {
        $this->user = $User;

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
    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_id', 'text', 'visibility', 'creation_date', 'update_date', 'User_id', 'gn_id'];
    } */
}
