<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\IntrigueHasGroupe.
 *
 * @Table(name="intrigue_has_groupe", indexes={@Index(name="fk_intrigue_has_groupe_groupe1_idx", columns={"groupe_id"}), @Index(name="fk_intrigue_has_groupe_intrigue1_idx", columns={"intrigue_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseIntrigueHasGroupe", "extended":"IntrigueHasGroupe"})
 */
class BaseIntrigueHasGroupe
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Intrigue", inversedBy="intrigueHasGroupes", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="intrigue_id", referencedColumnName="id", nullable=false)
     */
    protected $intrigue;

    /**
     * @ManyToOne(targetEntity="Groupe", inversedBy="intrigueHasGroupes")
     *
     * @JoinColumn(name="groupe_id", referencedColumnName="id", nullable=false)
     */
    protected $groupe;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\IntrigueHasGroupe
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
     * @return \App\Entity\IntrigueHasGroupe
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
     * Set Groupe entity (many to one).
     *
     * @return \App\Entity\IntrigueHasGroupe
     */
    public function setGroupe(Groupe $groupe = null)
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     *
     * @return \App\Entity\Groupe
     */
    public function getGroupe()
    {
        return $this->groupe;
    }

    public function __sleep()
    {
        return ['id', 'intrigue_id', 'groupe_id'];
    }
}
