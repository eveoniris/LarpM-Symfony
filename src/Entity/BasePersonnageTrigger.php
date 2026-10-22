<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TriggerType;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_trigger')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_trigger_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageTrigger', 'extended' => 'PersonnageTrigger'])]
abstract class BasePersonnageTrigger
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected ?string $tag = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $done = false;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageTriggers')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

    /** GN pour lequel le trigger a été donné (null pour les triggers antérieurs à l'historique) */
    #[ManyToOne(targetEntity: Gn::class)]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Gn $gn = null;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $created_at = null;

    /** Suppression logique : date à laquelle le trigger a été consommé ou retiré */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $deleted_at = null;

    /** Choix fait avec ce trigger (potion) */
    #[ManyToOne(targetEntity: Potion::class)]
    #[JoinColumn(name: 'potion_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Potion $potion = null;

    /** Choix fait avec ce trigger (sort) */
    #[ManyToOne(targetEntity: Sort::class)]
    #[JoinColumn(name: 'sort_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Sort $sort = null;

    /** Libellé du choix, conservé même si l'entité choisie disparaît */
    #[Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $choice_label = null;

    /** Origine du trigger (ex. achat de la compétence), null pour un don par GN */
    #[Column(type: Types::STRING, length: 30, nullable: true)]
    protected ?string $source = null;

    /**
     * Get the value of done.
     */
    public function getDone(): bool
    {
        return $this->done;
    }

    /**
     * Set the value of done.
     */
    public function setDone(bool $done): static
    {
        $this->done = $done;

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
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
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
     * Get the value of tag.
     */
    public function getTag(): string|TriggerType|null
    {
        return $this->tag ? TriggerType::tryFrom($this->tag) : null;
    }

    /**
     * Set the value of tag.
     */
    public function setTag(string|TriggerType|null $tag): static
    {
        if ($tag instanceof TriggerType) {
            $this->tag = $tag->value;

            return $this;
        }

        $this->tag = $tag;

        return $this;
    }

    public function getGn(): ?Gn
    {
        return $this->gn;
    }

    public function setGn(?Gn $gn): static
    {
        $this->gn = $gn;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTimeInterface $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    public function getPotion(): ?Potion
    {
        return $this->potion;
    }

    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    public function getChoiceLabel(): ?string
    {
        return $this->choice_label;
    }
}
