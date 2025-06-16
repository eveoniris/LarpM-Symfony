<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'experience_usage')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_experience_usage_competence1_idx')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_experience_usage_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseExperienceUsage', 'extended' => 'ExperienceUsage'])]
abstract class BaseExperienceUsage
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected DateTime $operation_date;

    #[Column(type: Types::INTEGER)]
    protected int $xp_use;

    #[ManyToOne(targetEntity: Competence::class, inversedBy: 'experienceUsages')]
    #[JoinColumn(name: 'competence_id', referencedColumnName: 'id', nullable: false)]
    protected Competence $competence;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'experienceUsages')]
    #[JoinColumn(name: 'personnage_id', referencedColumnName: 'id', nullable: false)]
    protected Personnage $personnage;

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOperationDate(DateTime $operation_date): static
    {
        $this->operation_date = $operation_date;

        return $this;
    }

    public function getOperationDate(): DateTime
    {
        return $this->operation_date;
    }

    public function setXpUse(int $xp_use): static
    {
        $this->xp_use = $xp_use;

        return $this;
    }

    public function getXpUse(): int
    {
        return $this->xp_use;
    }

    public function setCompetence(Competence $competence = null): static
    {
        $this->competence = $competence;

        return $this;
    }

    public function getCompetence(): Competence
    {
        return $this->competence;
    }

    public function setPersonnage(Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }

    /* public function __sleep()
    {
        return ['id', 'operation_date', 'xp_use', 'competence_id', 'personnage_id'];
    } */
}
