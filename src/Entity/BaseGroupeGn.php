<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\oOne;

#[ORM\Entity]
#[ORM\Table(name: 'groupe_gn')]
#[ORM\Index(columns: ['groupe_id'], name: 'fk_groupe_gn_groupe1_idx')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_groupe_gn_gn1_idx')]
#[ORM\Index(columns: ['responsable_id'], name: 'fk_groupe_gn_participant1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseGroupeGn', 'extended' => 'GroupeGn'])]
abstract class BaseGroupeGn
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $free = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true)]
    protected ?string $code = null;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected bool $jeu_maritime = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected bool $jeu_strategique = false;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $place_available = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $agents = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $bateaux = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $sieges = 0;

    #[Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $initiative = 0;

    #[ORM\OneToMany(mappedBy: 'groupeGn', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_gn_id', nullable: 'false')]
    protected Collection $participants;

    #[ORM\OneToMany(mappedBy: 'groupeGn', targetEntity: GroupeGnOrdre::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_gn_id', nullable: 'false')]
    #[ORM\orderBy(['ordre' => 'ASC'])]
    protected Collection $groupeGnOrdres;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Groupe $groupe;

    #[ORM\ManyToOne(targetEntity: Gn::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected Gn $gn;

    #[ORM\ManyToOne(targetEntity: Participant::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'responsable_id', referencedColumnName: 'id', nullable: 'false')]
    protected Participant $participant;

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'suzerain_id', referencedColumnName: 'id', nullable: 'false')]
    protected Personnage $suzerain;    

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->groupeGnOrdres = new ArrayCollection();
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
     * Set the value of free.
     */
    public function setFree(bool $free): static
    {
        $this->free = $free;

        return $this;
    }

    /**
     * Get the value of free.
     */
    public function getFree(): bool
    {
        return $this->free;
    }

    /**
     * Set the value of code.
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set the value of jeu_maritime.
     */
    public function setJeuMaritime(bool $jeu_maritime): static
    {
        $this->jeu_maritime = $jeu_maritime;

        return $this;
    }

    /**
     * Get the value of jeu_maritime.
     */
    public function getJeuMaritime(): bool
    {
        return $this->jeu_maritime;
    }

    /**
     * Set the value of jeu_strategique.
     */
    public function setJeuStrategique(bool $jeu_strategique): static
    {
        $this->jeu_strategique = $jeu_strategique;

        return $this;
    }

    /**
     * Get the value of jeu_strategique.
     */
    public function getJeuStrategique(): bool
    {
        return $this->jeu_strategique;
    }

    /**
     * Set the value of place_available.
     */
    public function setPlaceAvailable(int $place_available): GroupeGn
    {
        $this->place_available = $place_available;

        return $this;
    }

    /**
     * Get the value of place_available.
     */
    public function getPlaceAvailable(): int
    {
        return $this->place_available;
    }

    /**
     * Set the value of agents.
     */
    public function setAgents(int $agents): static
    {
        $this->agents = $agents;

        return $this;
    }

    /**
     * Get the value of agents.
     */
    public function getAgents(): int
    {
        return $this->agents;
    }

    /**
     * Set the value of bateaux.
     */
    public function setBateaux(int $bateaux): static
    {
        $this->bateaux = $bateaux;

        return $this;
    }

    /**
     * Get the value of bateaux.
     */
    public function getBateaux(): int
    {
        return $this->bateaux;
    }

    /**
     * Set the value of sieges.
     */
    public function setSieges(int $sieges): static
    {
        $this->sieges = $sieges;

        return $this;
    }

    /**
     * Get the value of sieges.
     */
    public function getSieges(): int
    {
        return $this->sieges;
    }

    /**
     * Set the value of initiative.
     */
    public function setInitiative(int $initiative): static
    {
        $this->initiative = $initiative;

        return $this;
    }

    /**
     * Get the value of initiative.
     */
    public function getInitiative(): int
    {
        return $this->initiative;
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
     * Set Groupe entity (many to one).
     */
    public function setGroupe(Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     */
    public function getGroupe(): Groupe
    {
        return $this->groupe;
    }

    /**
     * Set Gn entity (many to one).
     */
    public function setGn(Gn $gn = null): static
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /**
     * Set Participant entity (many to one).
     */
    public function setParticipant(Participant $participant = null): static
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     */
    public function getParticipant(): Participant
    {
        return $this->participant;
    }

    /**
     * Set Personnage entity (many to one).
     */
    public function setSuzerain(Personnage $suzerain = null): GroupeGn
    {
        $this->suzerain = $suzerain;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     */
    public function getSuzerain(): Personnage
    {
        return $this->suzerain;
    }

    /**
     * Add GroupeGnOrdre entity to collection.
     */
    public function addGroupeGnOrdre(GroupeGnOrdre $groupeGnOrdre): static
    {
        $groupeGnOrdre->addGroupeGn($this);
        $this->groupeGnOrdres[] = $groupeGnOrdre;

        return $this;
    }

    /**
     * Remove GroupeGnOrdre entity from collection.
     */
    public function removeGroupeGnOrdre(GroupeGnOrdre $groupeGnOrdre): static
    {
        $groupeGnOrdre->removeGroupeGn($this);
        $this->groupeGnOrdres->removeElement($groupeGnOrdre);

        return $this;
    }

    /**
     * Get GroupeGnOrdre entity collection.
     */
    public function getGroupeGnOrdres(): Collection
    {
        return $this->groupeGnOrdres;
    }

    public function __sleep()
    {
        return ['id', 'groupe_id', 'gn_id', 'responsable_id', 'free', 'code', 'jeu_maritime', 'jeu_strategique', 'place_available'];
    }
}
