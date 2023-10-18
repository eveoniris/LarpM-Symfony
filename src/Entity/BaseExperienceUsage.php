<?php

namespace App\Entity;

/**
 * App\Entity\ExperienceUsage.
 *
 * @Table(name="experience_usage", indexes={@Index(name="fk_experience_usage_competence1_idx", columns={"competence_id"}), @Index(name="fk_experience_usage_personnage1_idx", columns={"personnage_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseExperienceUsage", "extended":"ExperienceUsage"})
 */
class BaseExperienceUsage
{
    /**
     * @Id
     *
     * @Column(type="integer")
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="datetime")
     */
    protected $operation_date;

    /**
     * @Column(type="integer")
     */
    protected $xp_use;

    /**
     * @ManyToOne(targetEntity="Competence", inversedBy="experienceUsages")
     *
     * @JoinColumn(name="competence_id", referencedColumnName="id", nullable=false)
     */
    protected $competence;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="experienceUsages")
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
     * @return \App\Entity\ExperienceUsage
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
     * Set the value of operation_date.
     *
     * @param \DateTime $operation_date
     *
     * @return \App\Entity\ExperienceUsage
     */
    public function setOperationDate($operation_date)
    {
        $this->operation_date = $operation_date;

        return $this;
    }

    /**
     * Get the value of operation_date.
     *
     * @return \DateTime
     */
    public function getOperationDate()
    {
        return $this->operation_date;
    }

    /**
     * Set the value of xp_use.
     *
     * @param int $xp_use
     *
     * @return \App\Entity\ExperienceUsage
     */
    public function setXpUse($xp_use)
    {
        $this->xp_use = $xp_use;

        return $this;
    }

    /**
     * Get the value of xp_use.
     *
     * @return int
     */
    public function getXpUse()
    {
        return $this->xp_use;
    }

    /**
     * Set Competence entity (many to one).
     *
     * @return \App\Entity\ExperienceUsage
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
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\ExperienceUsage
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
        return ['id', 'operation_date', 'xp_use', 'competence_id', 'personnage_id'];
    }
}