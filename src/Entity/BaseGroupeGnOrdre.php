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
#[ORM\Table(name: 'groupe_gn_ordre')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeGnOrdre', 'extended' => 'GroupeGnOrdre'])]
abstract class BaseGroupeGnOrdre
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $ordre;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    protected string $extra;

    #[ManyToOne(targetEntity: GroupeGn::class, inversedBy: 'groupeGnOrdres')]
    #[JoinColumn(name: 'groupe_gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected GroupeGn $groupeGn;

    #[ManyToOne(targetEntity: Territoire::class, inversedBy: 'groupeGnOrdres')]
    #[JoinColumn(name: 'cible_id', referencedColumnName: 'id', nullable: 'false')]
    protected Territoire $cible;

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
     * Set the value of ordre.
     */
    public function setOrdre(bool $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get the value of ordre.
     */
    public function getOrdre(): bool
    {
        return $this->ordre;
    }

    /**
     * Set the value of extra.
     */
    public function setExtra(bool $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get the value of extra.
     */
    public function getExtra(): bool
    {
        return $this->extra;
    }

    /**
     * Set the value of cible.
     */
    public function setCible(Territoire $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     */
    public function getCible(): Territoire
    {
        return $this->cible;
    }

    /**
     * Set GroupeGn entity (many to one).
     */
    public function setGroupeGN(GroupeGn $groupeGn): static
    {
        $this->groupeGn = $groupeGn;

        return $this;
    }

    /**
     * Get GroupeGn entity (many to one).
     */
    public function getGroupeGn(): GroupeGn
    {
        return $this->groupeGn;
    }

    /* public function __sleep()
    {
        return ['id', 'ordre', 'groupe_gn_id', 'cible_id'];
    } */
}
