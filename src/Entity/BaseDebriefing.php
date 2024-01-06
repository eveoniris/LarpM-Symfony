<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * App\Entity\Debriefing.
 */
#[Table(name: 'debriefing')]
#[Index(columns: ['groupe_id'], name: 'fk_debriefing_groupe1_idx')]
#[Index(columns: ['User_id'], name: 'fk_debriefing_User1_idx')]
#[Index(columns: ['gn_id'], name: 'fk_debriefing_gn1_idx')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['base' => 'BaseDebriefing', 'extended' => 'Debriefing'])]
class BaseDebriefing
{
    #[Id]
    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected ?string $titre = null;

    #[Column(name: '`text`', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $text = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $visibility = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[Column(name: 'documentUrl', type: \Doctrine\DBAL\Types\Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[ManyToOne(targetEntity: 'Groupe', inversedBy: 'debriefings')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupe;

    #[ManyToOne(targetEntity: 'User', inversedBy: 'debriefings')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[ManyToOne(targetEntity: 'User', inversedBy: 'playerDebriefings')]
    #[JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    protected User $player;

    #[ManyToOne(targetEntity: 'Gn', inversedBy: 'debriefings')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id')]
    protected Gn $gn;


    /**
     * Set the value of id.
     *
     * @return Debriefing
     */
    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of titre.
     *
     * @return Debriefing
     */
    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get the value of titre.
     *
     * @return string
     */
    public function getTitre(): string
    {
        return $this->titre ?? '';
    }

    /**
     * Set the value of text.
     *
     * @return Debriefing
     */
    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get the value of text.
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text ?? '';
    }

    /**
     * Set the value of visibility.
     *
     * @return Debriefing
     */
    public function setVisibility(?string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get the value of visibility.
     *
     * @return string
     */
    public function getVisibility(): string
    {
        return $this->visibility ?? '';
    }

    /**
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     *
     * @return Debriefing
     */
    public function setCreationDate(?\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return \DateTime
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
     *
     * @param \DateTime $update_date
     *
     * @return Debriefing
     */
    public function setUpdateDate(?\DateTime $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get the value of update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function getDocumentUrl(): string
    {
        return $this->documentUrl ?? '';
    }

    public function setDocumentUrl(mixed $documentUrl): static
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Set Groupe entity (many to one).
     *
     * @return Debriefing
     */
    public function setGroupe(Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     *
     * @return Groupe
     */
    public function getGroupe(): Groupe
    {
        return $this->groupe;
    }

    /**
     * Set User entity (many to one).
     *
     * @return Debriefing
     */
    public function setUser(User $User = null): static
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User entity (many to one).
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set player User entity (many to one).
     *
     * @return Debriefing
     */
    public function setPlayer(User $player = null): static
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player User entity (many to one).
     *
     * @return User
     */
    public function getPlayer(): User
    {
        return $this->player;
    }

    /**
     * Set Gn entity (many to one).
     *
     * @return Debriefing
     */
    public function setGn(Gn $gn = null): static
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     *
     * @return Gn
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /* public function __sleep()
    {
        return ['id', 'titre', 'text', 'visibility', 'creation_date', 'update_date', 'groupe_id', 'User_id', 'gn_id'];
    } */
}
