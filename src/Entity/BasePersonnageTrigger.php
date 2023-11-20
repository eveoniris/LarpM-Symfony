<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageTrigger.
 *
 * @Table(name="personnage_trigger", indexes={@Index(name="fk_trigger_personnage1_idx", columns={"personnage_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageTrigger", "extended":"PersonnageTrigger"})
 */
class BasePersonnageTrigger
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $tag;

    /**
     * @Column(type="boolean")
     */
    protected $done;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageTriggers")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageTrigger
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
     * Set the value of tag.
     *
     * @param string $tag
     *
     * @return \App\Entity\PersonnageTrigger
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get the value of tag.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set the value of done.
     *
     * @param bool $done
     *
     * @return \App\Entity\PersonnageTrigger
     */
    public function setDone($done)
    {
        $this->done = $done;

        return $this;
    }

    /**
     * Get the value of done.
     *
     * @return bool
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageTrigger
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

    public function __sleep()
    {
        return ['id', 'personnage_id', 'tag', 'done'];
    }
}
