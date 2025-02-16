<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_secondaire_competence')]
#[ORM\Index(columns: ['personnage_secondaire_id'], name: 'fk_personnage_secondaire_competences_personnage_secondaire_idx')]
#[ORM\Index(columns: ['competence_id'], name: 'fk_personnage_secondaire_competences_competence1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageSecondaireCompetence', 'extended' => 'PersonnageSecondaireCompetence'])]
abstract class BasePersonnageSecondaireCompetence
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ManyToOne(targetEntity: PersonnageSecondaire::class, cascade: ['persist'], inversedBy: 'personnageSecondaireCompetences')]
    #[JoinColumn(name: 'personnage_secondaire_id', referencedColumnName: 'id', nullable: 'false')]
    protected PersonnageSecondaire $personnageSecondaire;

    #[ManyToOne(targetEntity: Competence::class, inversedBy: 'personnageSecondaireCompetences', cascade: ['persist'])]
    #[JoinColumn(name: 'competence_id', referencedColumnName: 'id', nullable: 'false')]
    protected Competence $competence;

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set PersonnageSecondaire entity (many to one).
     */
    public function setPersonnageSecondaire(PersonnageSecondaire $personnageSecondaire = null): static
    {
        $this->personnageSecondaire = $personnageSecondaire;

        return $this;
    }

    /**
     * Get PersonnageSecondaire entity (many to one).
     */
    public function getPersonnageSecondaire(): PersonnageSecondaire
    {
        return $this->personnageSecondaire;
    }

    /**
     * Set Competence entity (many to one).
     */
    public function setCompetence(Competence $competence = null): static
    {
        $this->competence = $competence;

        return $this;
    }

    /**
     * Get Competence entity (many to one).
     */
    public function getCompetence(): ?Competence
    {
        return $this->competence;
    }

    /* public function __sleep()
    {
        return ['id', 'personnage_secondaire_id', 'competence_id'];
    } */
}
