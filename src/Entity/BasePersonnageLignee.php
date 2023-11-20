<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageLignee.
 *
 * @Table(name="personnages_lignee")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageLignee", "extended":"PersonnageLignee"})
 */
class BasePersonnageLignee
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="PersonnageLignee")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="PersonnageLignee")
     *
     * @JoinColumn(name="parent1_id", referencedColumnName="id", nullable=false)
     */
    protected $parent1;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="PersonnageLignee")
     *
     * @JoinColumn(name="parent2_id", referencedColumnName="id", nullable=false)
     */
    protected $parent2;

    /**
     * @ManyToOne(targetEntity="Lignee", inversedBy="PersonnageLignee")
     *
     * @JoinColumn(name="lignee_id", referencedColumnName="id", nullable=false)
     */
    protected $lignee;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function setPersonnage(Personnage $personnage = null)
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getPersonnage()
    {
        return $this->personnage;
    }

    /**
     * Set Parent1 entity (many to one).
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function setParent1(Personnage $parent1 = null)
    {
        $this->parent1 = $parent1;

        return $this;
    }

    /**
     * Get Parent1 entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getParent1()
    {
        return $this->parent1;
    }

    /**
     * Set Parent2 entity (many to one).
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function setParent2(Personnage $parent2 = null)
    {
        $this->parent2 = $parent2;

        return $this;
    }

    /**
     * Get Parent2 entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getParent2()
    {
        return $this->parent2;
    }

    /**
     * Set Lignee entity (many to one).
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function setLignee(Lignee $lignee = null)
    {
        $this->lignee = $lignee;

        return $this;
    }

    /**
     * Get Lignee entity (many to one).
     *
     * @return \App\Entity\PersonnageLignee
     */
    public function getLignee()
    {
        return $this->lignee;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'parent1_id', 'parent2_id', 'ligne_id'];
    }
}
