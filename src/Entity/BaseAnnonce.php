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
#[ORM\Table(name: 'annonce')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_annonce_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseAnnonce', 'extended' => 'Annonce'])]
class BaseAnnonce
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'title', type: Types::STRING, length: 45)]
    protected string $title = '';

    #[Column(name: 'text', type: Types::TEXT)]
    protected string $text = '';

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTime $creation_date = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTime $update_date = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $archive = false;

    #[ManyToOne(targetEntity: Gn::class, inversedBy: 'annonces')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id')]
    protected ?Gn $gn = null;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setCreationDate(\DateTime $creation_date): self
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setUpdateDate(\DateTime $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setArchive(bool $archive): self
    {
        $this->archive = $archive;

        return $this;
    }

    public function getArchive(): bool
    {
        return $this->archive;
    }

    public function setGn(?Gn $gn = null): self
    {
        $this->gn = $gn;

        return $this;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    /* public function __sleep()
    {
        return ['id', 'title', 'text', 'creation_date', 'update_date', 'archive', 'gn_id'];
    } */
}
