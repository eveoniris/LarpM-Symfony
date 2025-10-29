<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'experience_gain')]
#[ORM\Index(columns: ['personnage_id'], name: 'fk_experience_gain_personnage1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseExperienceGain', 'extended' => 'ExperienceGain'])]
abstract class BaseExperienceGain
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    protected string $explanation;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected \DateTime $operation_date;

    #[Column(type: Types::INTEGER)]
    protected int $xp_gain;

    #[ManyToOne(targetEntity: Personnage::class, inversedBy: 'experienceGains')]
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

    public function setExplanation(string $explanation): static
    {
        $this->explanation = substr($explanation, 0, 100);

        return $this;
    }

    public function getExplanation(): string
    {
        return $this->explanation ?? '';
    }

    public function setOperationDate(\DateTime $operation_date): static
    {
        $this->operation_date = $operation_date;

        return $this;
    }

    public function getOperationDate(): \DateTime
    {
        return $this->operation_date;
    }

    public function setXpGain(int $xp_gain): static
    {
        $this->xp_gain = $xp_gain;

        return $this;
    }

    public function getXpGain(): int
    {
        return $this->xp_gain;
    }

    public function setPersonnage(?Personnage $personnage = null): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getPersonnage(): Personnage
    {
        return $this->personnage;
    }
}
