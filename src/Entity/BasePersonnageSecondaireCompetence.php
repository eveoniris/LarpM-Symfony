<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\PersonnageSecondaireCompetence
 *
 * @Table(name="personnage_secondaire_competence", indexes={@Index(name="fk_personnage_secondaire_competences_personnage_secondaire_idx", columns={"personnage_secondaire_id"}), @Index(name="fk_personnage_secondaire_competences_competence1_idx", columns={"competence_id"})})
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BasePersonnageSecondaireCompetence", "extended":"PersonnageSecondaireCompetence"})
 */
class BasePersonnageSecondaireCompetence
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @ManyToOne(targetEntity="PersonnageSecondaire", inversedBy="personnageSecondaireCompetences", cascade={"persist"})
     * @JoinColumn(name="personnage_secondaire_id", referencedColumnName="id", nullable=false)
     */
    protected $personnageSecondaire;

    /**
     * @ManyToOne(targetEntity="Competence", inversedBy="personnageSecondaireCompetences", cascade={"persist"})
     * @JoinColumn(name="competence_id", referencedColumnName="id", nullable=false)
     */
    protected $competence;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\PersonnageSecondaireCompetence
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     *
     * @return \App\Entity\PersonnageSecondaireCompetence
     */
    public function setPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire = null)
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     *
     * @return \App\Entity\PersonnageSecondaire
     */
    public function getPersonnageSecondaire()
    {
        return $this->personnageSecondaire;
    }

    /**
     * Set Competence entity (many to one).
     *
     * @return \App\Entity\PersonnageSecondaireCompetence
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

    public function __sleep()
    {
        return array('id', 'personnage_secondaire_id', 'competence_id');
    }
}
