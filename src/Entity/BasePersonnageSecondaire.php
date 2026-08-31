<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

#[ORM\Entity]
#[ORM\Table(name: 'personnage_secondaire')]
#[ORM\Index(columns: ['classe_id'], name: 'fk_personnage_secondaire_classe1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePersonnageSecondaire', 'extended' => 'PersonnageSecondaire'])]
abstract class BasePersonnageSecondaire
{
    #[Id, Column(type: Types::INTEGER), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /** @var Collection<int, Participant> */
    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: false)]
    protected Collection $participants;

    /** @var Collection<int, PersonnageSecondaireCompetence> */
    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: PersonnageSecondaireCompetence::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: false)]
    protected Collection $personnageSecondaireCompetences;

    /** @var Collection<int, PersonnageSecondairesCompetences> */
    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: PersonnageSecondairesCompetences::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: false)]
    protected Collection $personnageSecondairesCompetences;

    /** @var Collection<int, PersonnageSecondairesSkills> */
    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: PersonnageSecondairesSkills::class, cascade: ['persist'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: false)]
    protected Collection $personnageSecondairesSkills;

    #[ManyToOne(targetEntity: Classe::class, inversedBy: 'personnageSecondaires')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id', nullable: false)]
    protected Classe $classe;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->personnageSecondaireCompetences = new ArrayCollection();
        $this->personnageSecondairesCompetences = new ArrayCollection();
        $this->personnageSecondairesSkills = new ArrayCollection();
    }

    /**
     * Add Participant entity to collection (one to many).
     */
    public function addParticipant(Participant $participant): static
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Add PersonnageSecondaireCompetence entity to collection (one to many).
     */
    public function addPersonnageSecondaireCompetence(PersonnageSecondaireCompetence $personnageSecondaireCompetence): static
    {
        $this->personnageSecondaireCompetences[] = $personnageSecondaireCompetence;

        return $this;
    }

    /**
     * Get Classe entity (many to one).
     */
    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    /**
     * Set Classe entity (many to one).
     */
    public function setClasse(?Classe $classe = null): static
    {
        $this->classe = $classe;

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
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Participant entity collection (one to many).
     *
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * Get PersonnageSecondaireCompetence entity collection (one to many).
     *
     * @return Collection<int, PersonnageSecondaireCompetence>
     */
    public function getPersonnageSecondaireCompetences(): Collection
    {
        return $this->personnageSecondaireCompetences;
    }

    /**
     * Remove Participant entity from collection (one to many).
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Remove PersonnageSecondaireCompetence entity from collection (one to many).
     */
    public function removePersonnageSecondaireCompetence(PersonnageSecondaireCompetence $personnageSecondaireCompetence): static
    {
        $this->personnageSecondaireCompetences->removeElement($personnageSecondaireCompetence);

        return $this;
    }

    /* public function __sleep()
     * {
     * return ['id', 'classe_id'];
     * } */
}
