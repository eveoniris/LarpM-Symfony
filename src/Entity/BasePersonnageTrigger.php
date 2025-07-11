<?php

namespace App\Entity;

use App\Enum\TriggerType;
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
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45)]
    protected ?string $tag = null;

    #[Column(type: Types::BOOLEAN)]
    protected bool $done = false;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnageTriggers')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

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
        return TriggerType::tryFrom($this->tag);
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
}
