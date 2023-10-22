<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'background')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_background_groupe1_idx')]
#[ORM\Index(columns: ['user_id'], name: 'fk_background_user1_idx')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_background_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseBackground', 'extended' => 'Background'])]
class BaseBackground
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'titre', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $titre = '';

    #[Column(name: 'text', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $text = null;

    #[Column(name: 'visibility', type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $visibility = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $creation_date;

    #[Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $update_date;

    #[ManyToOne(targetEntity: Groupe::class, inversedBy: 'backgrounds')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Groupe $groupe = null;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'backgrounds')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?User $User = null;

    #[ManyToOne(targetEntity: Gn::class, inversedBy: 'backgrounds')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id')]
    protected ?Gn $gn = null;

    public function __construct()
    {
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setCreationDate(?\DateTime $creation_date): self
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(?\DateTime $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setGroupe(Groupe $groupe = null): self
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setUser(User $User = null)
    {
        $this->User = $User;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setGn(Gn $gn = null): self
    {
        $this->gn = $gn;

        return $this;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function __sleep()
    {
        return ['id', 'titre', 'text', 'visibility', 'creation_date', 'update_date', 'groupe_id', 'User_id', 'gn_id'];
    }
}
