<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: 'false')]
    protected Collection $participants;

    #[OneToMany(mappedBy: 'personnageSecondaire', cascade: ['persist'], targetEntity: PersonnageSecondaireCompetence::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: 'false')]
    protected Collection $personnageSecondaireCompetences;

    #[OneToMany(mappedBy: 'personnageSecondaire', cascade: ['persist'], targetEntity: PersonnageSecondairesCompetences::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: 'false')]
    protected Collection $personnageSecondairesCompetences;

    #[OneToMany(mappedBy: 'personnageSecondaire', cascade: ['persist'], targetEntity: PersonnageSecondairesSkills::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: 'false')]
    protected Collection $personnageSecondairesSkills;

    #[OneToMany(mappedBy: 'personnageSecondaire', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'personnage_secondaire_id', nullable: 'false')]
    protected Collection $users;

    #[ManyToOne(targetEntity: Classe::class, inversedBy: 'personnageSecondaires')]
    #[JoinColumn(name: 'classe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Classe $classe;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->personnageSecondaireCompetences = new ArrayCollection();
        $this->personnageSecondairesCompetences = new ArrayCollection();
        $this->personnageSecondairesSkills = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
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
     * Remove Participant entity from collection (one to many).
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Get Participant entity collection (one to many).
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
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
     * Remove PersonnageSecondaireCompetence entity from collection (one to many).
     */
    public function removePersonnageSecondaireCompetence(PersonnageSecondaireCompetence $personnageSecondaireCompetence): static
    {
        $this->personnageSecondaireCompetences->removeElement($personnageSecondaireCompetence);

        return $this;
    }

    /**
     * Get PersonnageSecondaireCompetence entity collection (one to many).
     */
    public function getPersonnageSecondaireCompetences(): Collection
    {
        return $this->personnageSecondaireCompetences;
    }

    /**
     * Add User entity to collection (one to many).
     */
    public function addUser(User $user): static
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove User entity from collection (one to many).
     */
    public function removeUser(User $user): ?User
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Get User entity collection (one to many).
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * Set Classe entity (many to one).
     */
    public function setClasse(Classe $classe = null): static
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * Get Classe entity (many to one).
     */
    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    /* public function __sleep()
    {
        return ['id', 'classe_id'];
    } */
}
