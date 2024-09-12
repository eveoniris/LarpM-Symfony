<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\Debriefing.
 */
#[ORM\Entity]
#[ORM\Table(name: 'debriefing')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_debriefing_groupe1_idx')]
#[ORM\Index(columns: ['User_id'], name: 'fk_debriefing_User1_idx')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_debriefing_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseDebriefing', 'extended' => 'Debriefing'])]
class BaseDebriefing
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 45)]
    protected ?string $titre = null;

    #[ORM\Column(name: '`text`', type: Types::TEXT, nullable: true)]
    protected ?string $text = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $visibility = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date = null;

    #[ORM\Column(name: 'documentUrl', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $documentUrl = null;

    #[ORM\ManyToOne(targetEntity: 'Groupe', inversedBy: 'debriefings')]
    #[ORM\JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: false)]
    protected Groupe $groupe;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'debriefings')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'playerDebriefings')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false)]
    protected ?User $player;

    #[ORM\ManyToOne(targetEntity: 'Gn', inversedBy: 'debriefings')]
    #[ORM\JoinColumn(name: 'gn_id', referencedColumnName: 'id')]
    protected ?Gn $gn;

    public function __construct()
    {
    }

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
     */
    public function getVisibility(): string
    {
        return $this->visibility ?? '';
    }

    /**
     * Set the value of creation_date.
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
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of update_date.
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
    public function setGroupe(?Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
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
    public function setUser(?User $User = null): static
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

    /**
     * Set player User entity (many to one).
     *
     * @return Debriefing
     */
    public function setPlayer(?User $player = null): static
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player User entity (many to one).
     */
    public function getPlayer(): ?User
    {
        return $this->player;
    }

    /**
     * Set Gn entity (many to one).
     *
     * @return Debriefing
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
        return ['id', 'titre', 'text', 'visibility', 'creation_date', 'update_date', 'groupe_id', 'User_id', 'gn_id'];
    } */
}
