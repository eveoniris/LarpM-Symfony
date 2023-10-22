<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\CompetenceAttribute.
 *
 * @Table(name="competence_attribute", indexes={@Index(name="fk_competence_has_attribute_type_attribute_type1_idx", columns={"attribute_type_id"}), @Index(name="fk_competence_has_attribute_type_competence1_idx", columns={"competence_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseCompetenceAttribute", "extended":"CompetenceAttribute"})
 */
class BaseCompetenceAttribute
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true], columnDefinition: 'INT AUTO_INCREMENT')]
    protected int $competence_id;

    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true], columnDefinition: 'INT AUTO_INCREMENT')]
    protected int $attribute_type_id;

    /**
     * @Column(name="`value`", type="integer")
     */
    protected $value;

    /**
     * @ManyToOne(targetEntity="Competence", inversedBy="competenceAttributes")
     *
     * @JoinColumn(name="competence_id", referencedColumnName="id", nullable=false)
     */
    protected $competence;

    /**
     * @ManyToOne(targetEntity="AttributeType", inversedBy="competenceAttributes", cascade={"persist"})
     *
     * @JoinColumn(name="attribute_type_id", referencedColumnName="id", nullable=false)
     */
    protected $attributeType;

    public function __construct()
    {
    }

    /**
     * Set the value of competence_id.
     *
     * @param int $competence_id
     *
     * @return \App\Entity\CompetenceAttribute
     */
    public function setCompetenceId($competence_id)
    {
        $this->competence_id = $competence_id;

        return $this;
    }

    /**
     * Get the value of competence_id.
     *
     * @return int
     */
    public function getCompetenceId()
    {
        return $this->competence_id;
    }

    /**
     * Set the value of attribute_type_id.
     *
     * @param int $attribute_type_id
     *
     * @return \App\Entity\CompetenceAttribute
     */
    public function setAttributeTypeId($attribute_type_id)
    {
        $this->attribute_type_id = $attribute_type_id;

        return $this;
    }

    /**
     * Get the value of attribute_type_id.
     *
     * @return int
     */
    public function getAttributeTypeId()
    {
        return $this->attribute_type_id;
    }

    /**
     * Set the value of value.
     *
     * @param int $value
     *
     * @return \App\Entity\CompetenceAttribute
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set Competence entity (many to one).
     *
     * @return \App\Entity\CompetenceAttribute
     */
    public function setCompetence(Competence $competence = null)
    {
        $this->competence = $competence;

        return $this;
    }

    /**
     * Get Competence entity (many to one).
     *
     * @return \App\Entity\Competence
     */
    public function getCompetence()
    {
        return $this->competence;
    }

    /**
     * Set AttributeType entity (many to one).
     *
     * @return \App\Entity\CompetenceAttribute
     */
    public function setAttributeType(AttributeType $attributeType = null)
    {
        $this->attributeType = $attributeType;

        return $this;
    }

    /**
     * Get AttributeType entity (many to one).
     *
     * @return \App\Entity\AttributeType
     */
    public function getAttributeType()
    {
        return $this->attributeType;
    }

    public function __sleep()
    {
        return ['competence_id', 'attribute_type_id', 'value'];
    }
}
