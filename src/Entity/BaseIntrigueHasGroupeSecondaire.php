<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\IntrigueHasGroupeSecondaire.
 *
 * @Table(name="intrigue_has_groupe_secondaire", indexes={@Index(name="fk_intrigue_has_groupe_secondaire_intrigue1_idx", columns={"intrigue_id"}), @Index(name="fk_intrigue_has_groupe_secondaire_secondary_group1_idx", columns={"secondary_group_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseIntrigueHasGroupeSecondaire", "extended":"IntrigueHasGroupeSecondaire"})
 */
class BaseIntrigueHasGroupeSecondaire
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Intrigue", inversedBy="intrigueHasGroupeSecondaires", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="intrigue_id", referencedColumnName="id", nullable=false)
     */
    protected $intrigue;

    /**
     * @ManyToOne(targetEntity="SecondaryGroup", inversedBy="intrigueHasGroupeSecondaires")
     *
     * @JoinColumn(name="secondary_group_id", referencedColumnName="id", nullable=false)
     */
    protected $secondaryGroup;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\IntrigueHasGroupeSecondaire
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Intrigue entity (many to one).
     *
     * @return \App\Entity\IntrigueHasGroupeSecondaire
     */
    public function setIntrigue(Intrigue $intrigue = null)
    {
        $this->intrigue = $intrigue;

        return $this;
    }

    /**
     * Get Intrigue entity (many to one).
     *
     * @return \App\Entity\Intrigue
     */
    public function getIntrigue()
    {
        return $this->intrigue;
    }

    /**
     * Set SecondaryGroup entity (many to one).
     *
     * @return \App\Entity\IntrigueHasGroupeSecondaire
     */
    public function setSecondaryGroup(SecondaryGroup $secondaryGroup = null)
    {
        $this->secondaryGroup = $secondaryGroup;

        return $this;
    }

    /**
     * Get SecondaryGroup entity (many to one).
     *
     * @return \App\Entity\SecondaryGroup
     */
    public function getSecondaryGroup()
    {
        return $this->secondaryGroup;
    }

    public function __sleep()
    {
        return ['id', 'intrigue_id', 'secondary_group_id'];
    }
}
