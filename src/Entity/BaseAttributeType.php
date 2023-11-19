<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'attribute_type')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseAttributeType', 'extended' => 'AttributeType'])]
class BaseAttributeType
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(name: 'label', type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    protected string $label = '';

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\CompetenceAttribute>|\App\Entity\CompetenceAttribute[]
     */
    #[OneToMany(mappedBy: 'attributeType', targetEntity: CompetenceAttribute::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'attribute_type_id', nullable: 'false')]
    protected \Doctrine\Common\Collections\ArrayCollection|\Doctrine\Common\Collections\Collection $competenceAttributes;

    public function __construct()
    {
        $this->competenceAttributes = new ArrayCollection();
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
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

    public function addCompetenceAttribute(CompetenceAttribute $competenceAttribute): self
    {
        $this->competenceAttributes[] = $competenceAttribute;

        return $this;
    }

    public function removeCompetenceAttribute(CompetenceAttribute $competenceAttribute): self
    {
        $this->competenceAttributes->removeElement($competenceAttribute);

        return $this;
    }

    public function getCompetenceAttributes(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->competenceAttributes;
    }

    public function __sleep()
    {
        return ['label', 'id'];
    }
}
