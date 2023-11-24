<?php

namespace App\Entity;

use App\Repository\BaseUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity(repositoryClass: BaseUserRepository::class)]
#[ORM\Table(name: 'religion_description')]
#[ORM\Index(columns: ['religion_id'], name: 'fk_religion_description_religion1_idx')]
#[ORM\Index(columns: ['religion_level_id'], name: 'fk_religion_description_religion_level1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseReligionDescription', 'extended' => 'ReligionDescription'])]
abstract class BaseReligionDescription
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Personnage::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'personnage_id', referencedColumnName: 'id')]
    protected $religion;

    #[ORM\ManyToOne(targetEntity: ReligionLevel::class, inversedBy: 'religionDescriptions')]
    #[ORM\JoinColumn(name: 'religion_level_id', referencedColumnName: 'id', nullable: false)]
    protected ReligionLevel $religionLevel;

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
     * Set the value of description.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
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
    public function getReligion(): Religion
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
    public function getReligionLevel(): ReligionLevel
    {
        return $this->religionLevel;
    }

    public function __sleep()
    {
        return ['id', 'description', 'religion_id', 'religion_level_id'];
    }
}
