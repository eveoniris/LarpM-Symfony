<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'competence_attribute')]
#[ORM\Index(columns: ['attribute_type_id'], name: 'fk_competence_has_attribute_type_attribute_type1_idx')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_competence_has_attribute_type_competence1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseCompetenceAttribute', 'extended' => 'CompetenceAttribute'])]
class BaseCompetenceAttribute
{
    #[Id, Column(type: Types::INTEGER, columnDefinition: 'INT AUTO_INCREMENT')]
    protected int $competence_id;

    #[Id, Column(type: Types::INTEGER)]
    protected int $attribute_type_id;

    #[Id, Column(type: Types::INTEGER)]
    protected int $value;

    #[ORM\ManyToOne(targetEntity: Competence::class, inversedBy: 'competenceAttributes')]
    #[ORM\JoinColumn(name: 'competence_id', referencedColumnName: 'id', nullable: false)]
    protected Competence $competence;

    #[ORM\ManyToOne(targetEntity: AttributeType::class, inversedBy: 'competenceAttributes')]
    #[ORM\JoinColumn(name: 'attribute_type_id', referencedColumnName: 'id', nullable: false)]
    protected AttributeType $attributeType;

    /**
     * Set the value of competence_id.
     *
     * @return CompetenceAttribute
     */
    public function setCompetenceId(int $competence_id): static
    {
        $this->competence_id = $competence_id;

        return $this;
    }

    /**
     * Get the value of competence_id.
     */
    public function getCompetenceId(): int
    {
        return $this->competence_id;
    }

    /**
     * Set the value of attribute_type_id.
     *
     * @return CompetenceAttribute
     */
    public function setAttributeTypeId(int $attribute_type_id): static
    {
        $this->attribute_type_id = $attribute_type_id;

        return $this;
    }

    /**
     * Get the value of attribute_type_id.
     */
    public function getAttributeTypeId(): int
    {
        return $this->attribute_type_id;
    }

    /**
     * Set the value of value.
     *
     * @return CompetenceAttribute
     */
    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of value.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Set Competence entity (many to one).
     *
     * @return CompetenceAttribute
     */
    public function setCompetence(?Competence $competence = null): static
    {
        $this->competence = $competence;

        return $this;
    }

    /**
     * Get Competence entity (many to one).
     */
    public function getCompetence(): Competence
    {
        return $this->competence;
    }

    /**
     * Set AttributeType entity (many to one).
     *
     * @return CompetenceAttribute
     */
    public function setAttributeType(?AttributeType $attributeType = null): static
    {
        $this->attributeType = $attributeType;

        return $this;
    }

    /**
     * Get AttributeType entity (many to one).
     */
    public function getAttributeType(): AttributeType
    {
        return $this->attributeType;
    }

    /* public function __sleep()
    {
        return ['competence_id', 'attribute_type_id', 'value'];
    } */
}
