<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnages_religions')]
#[ORM\Index(columns: ['religion_id'], name: 'fk_personnage_religion_religion1_idx')]
#[ORM\Index(columns: ['religion_level_id'], name: 'fk_personnage_religion_religion_level1_idx')]
#[ORM\Index(columns: ['"personnage_id"'], name: 'fk_personnages_religions_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnagesReligions', 'extended' => 'PersonnagesReligions'])]
abstract class BasePersonnagesReligions
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: Religion::class, inversedBy: 'personnagesReligions')]
    #[JoinColumn(name: 'religion_id', referencedColumnName: 'id', nullable: 'false')]
    #[JoinColumn(name: 'religion_label', referencedColumnName: 'label', nullable: 'false')]
    #[ORM\OrderBy(['religion_label' => 'ASC'])]
    protected Religion $religion;

    #[ManyToOne(targetEntity: ReligionLevel::class, inversedBy: 'personnagesReligions')]
    #[JoinColumn(name: 'religion_level_id', referencedColumnName: 'id', nullable: 'false')]
    protected ReligionLevel $religionLevel;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'personnagesReligions')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $personnage;

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
     * Set Religion entity (many to one).
     */
    public function setReligion(Religion $religion = null): static
    {
        $this->religion = $religion;

        return $this;
    }

    /**
     * Get Religion entity (many to one).
     */
    public function getReligion(): ?Religion
    {
        return $this->religion;
    }

    /**
     * Set ReligionLevel entity (many to one).
     */
    public function setReligionLevel(ReligionLevel $religionLevel = null): static
    {
        $this->religionLevel = $religionLevel;

        return $this;
    }

    /**
     * Get ReligionLevel entity (many to one).
     */
    public function getReligionLevel(): ?ReligionLevel
    {
        return $this->religionLevel;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function __sleep()
    {
        return ['id', 'religion_id', 'religion_level_id', 'personnage_id'];
    }
}
