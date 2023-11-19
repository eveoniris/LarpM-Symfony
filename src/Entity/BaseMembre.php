<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Membre.
 *
 * @Table(name="membre", indexes={@Index(name="fk_personnage_groupe_secondaire_personnage1_idx", columns={"personnage_id"}), @Index(name="fk_personnage_groupe_secondaire_secondary_group1_idx", columns={"secondary_group_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseMembre", "extended":"Membre"})
 */
class BaseMembre
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $secret;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="membres")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="SecondaryGroup", inversedBy="membres")
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
     * @return \App\Entity\Membre
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of secret.
     *
     * @param bool $secret
     *
     * @return \App\Entity\Membre
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     *
     * @return bool
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\Membre
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
     * Set SecondaryGroup entity (many to one).
     *
     * @return \App\Entity\Membre
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
        return ['id', 'personnage_id', 'secondary_group_id', 'secret'];
    }
}
