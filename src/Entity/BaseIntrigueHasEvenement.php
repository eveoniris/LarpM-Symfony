<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\IntrigueHasEvenement.
 *
 * @Table(name="intrigue_has_evenement", indexes={@Index(name="fk_intrigue_has_evenement_evenement1_idx", columns={"evenement_id"}), @Index(name="fk_intrigue_has_evenement_intrigue1_idx", columns={"intrigue_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseIntrigueHasEvenement", "extended":"IntrigueHasEvenement"})
 */
class BaseIntrigueHasEvenement
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="Intrigue", inversedBy="intrigueHasEvenements", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="intrigue_id", referencedColumnName="id", nullable=false)
     */
    protected $intrigue;

    /**
     * @ManyToOne(targetEntity="Evenement", inversedBy="intrigueHasEvenements", cascade={"persist", "remove"})
     *
     * @JoinColumn(name="evenement_id", referencedColumnName="id", nullable=false)
     */
    protected $evenement;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\IntrigueHasEvenement
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
     * @return \App\Entity\IntrigueHasEvenement
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
     * Set Evenement entity (many to one).
     *
     * @return \App\Entity\IntrigueHasEvenement
     */
    public function setEvenement(Evenement $evenement = null)
    {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Get Evenement entity (many to one).
     *
     * @return \App\Entity\Evenement
     */
    public function getEvenement()
    {
        return $this->evenement;
    }

    public function __sleep()
    {
        return ['id', 'intrigue_id', 'evenement_id'];
    }
}
