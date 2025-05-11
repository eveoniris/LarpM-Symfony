<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;

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
    #[Id, Column(type: Types::INTEGER,), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::BOOLEAN)]
    protected ?bool $free = false;

    #[Column(type: Types::STRING, length: 10, nullable: true)]
    protected ?string $code = null;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $jeu_maritime = false;

    #[Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $jeu_strategique = false;

    #[Column(type: Types::INTEGER)]
    protected ?int $place_available = 0;

    #[Column(type: Types::INTEGER)]
    protected int $agents = 0;

    #[Column(type: Types::INTEGER)]
    protected int $bateaux = 0;

    #[Column(type: Types::INTEGER)]
    protected int $sieges = 0;

    #[Column(type: Types::INTEGER)]
    protected int $initiative = 0;

    #[ORM\OneToMany(mappedBy: 'groupeGn', targetEntity: Participant::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_gn_id', nullable: 'false')]
    #[ORM\OrderBy(['subscription_date' => 'ASC'])]
    protected Collection $participants;

    #[ORM\OneToMany(mappedBy: 'groupeGn', targetEntity: GroupeGnOrdre::class)]
    #[JoinColumn(name: 'id', referencedColumnName: 'groupe_gn_id', nullable: 'false')]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    protected Collection $groupeGnOrdres;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'groupe_id', referencedColumnName: 'id', nullable: 'false')]
    protected Groupe $groupe;

    #[ORM\ManyToOne(targetEntity: Gn::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'gn_id', referencedColumnName: 'id', nullable: 'false')]
    protected Gn $gn;

    #[ORM\ManyToOne(targetEntity: Participant::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'responsable_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Participant $participant;

    #[Column(length: 255, nullable: true)]
    private ?string $bateaux_localisation = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $suzerin = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $connetable = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $intendant = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $navigateur = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $camarilla = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Personnage $diplomate = null;

    /*
    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'groupeGns')]
    #[JoinColumn(name: 'suzerain_id', referencedColumnName: 'id', nullable: 'false')]
    protected ?Personnage $suzerain;*/

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->groupeGnOrdres = new ArrayCollection();
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
     * Add Participant entity to collection (one to many).
     */
    public function addParticipant(Participant $participant): static
    {
        $this->participants[] = $participant;

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
     * Set the value of agents.
     */
    public function setAgents(?int $agents): static
    {
        $this->agents = $agents ?? 0;

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
     * Set the value of bateaux.
     */
    public function setBateaux(?int $bateaux): static
    {
        $this->bateaux = $bateaux ?? 0;

        return $this;
    }

    public function getBateauxLocalisation(): ?string
    {
        return $this->bateaux_localisation;
    }

    public function setBateauxLocalisation(?string $bateaux_localisation): static
    {
        $this->bateaux_localisation = $bateaux_localisation;

        return $this;
    }

    public function getCamarilla(bool $strict = true): ?Personnage
    {
        return $this->camarilla ?? ($strict ? null : $this->getSuzerin($strict));
    }

    public function setCamarilla(?Personnage $camarilla): static
    {
        $this->camarilla = $camarilla;

        return $this;
    }

    /**
     * Get the value of code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set the value of code.
     */
    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getConnetable(bool $strict = true): ?Personnage
    {
        return $this->connetable ?? ($strict ? null : $this->getSuzerin($strict));
    }

    public function setConnetable(?Personnage $connetable): static
    {
        $this->connetable = $connetable;

        return $this;
    }

    public function getSuzerin(bool $strict = true): ?Personnage
    {
        // Par défaut le chef de groupe
        return $this->suzerin ?? ($strict ? null : $this->getParticipant()?->getPersonnage());
    }

    public function setSuzerin(?Personnage $suzerin): static
    {
        $this->suzerin = $suzerin;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     */
    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    /**
     * Set Participant entity (many to one).
     */
    public function setParticipant(?Participant $participant = null): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getDiplomate(): ?Personnage
    {
        return $this->diplomate;
    }

    public function setDiplomate(?Personnage $diplomate): static
    {
        $this->diplomate = $diplomate;

        return $this;
    }

    /**
     * Get the value of free.
     */
    public function getFree(): ?bool
    {
        return $this->free;
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
     * Get Gn entity (many to one).
     */
    public function getGn(): Gn
    {
        return $this->gn;
    }

    /**
     * Set Gn entity (many to one).
     */
    public function setGn(?Gn $gn = null): static
    {
        $this->gn = $gn;

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
     * Set Groupe entity (many to one).
     */
    public function setGroupe(?Groupe $groupe = null): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get GroupeGnOrdre entity collection.
     */
    public function getGroupeGnOrdres(): Collection
    {
        return $this->groupeGnOrdres;
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
     * Set Personnage entity (many to one).
     */
    /*public function setSuzerain(Personnage $suzerain = null): GroupeGn
    {
        $this->suzerain = $suzerain;

        return $this;
    }*/

    /**
     * Get Personnage entity (many to one).
     */
    /*public function getSuzerain(): ?Personnage
    {
        return $this->suzerain;
    }*/

    /**
     * Get the value of initiative.
     */
    public function getInitiative(): int
    {
        return $this->initiative;
    }

    /**
     * Set the value of initiative.
     */
    public function setInitiative(int $initiative): static
    {
        $this->initiative = $initiative;

        return $this;
    }

    public function getIntendant(bool $strict = true): ?Personnage
    {
        return $this->intendant ?? ($strict ? null : $this->getSuzerin($strict));
    }

    public function setIntendant(?Personnage $intendant): static
    {
        $this->intendant = $intendant;

        return $this;
    }

    /**
     * Get the value of jeu_maritime.
     */
    public function getJeuMaritime(): ?bool
    {
        return $this->jeu_maritime;
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
     * Get the value of jeu_strategique.
     */
    public function getJeuStrategique(): ?bool
    {
        return $this->jeu_strategique;
    }

    /**
     * Set the value of jeu_strategique.
     */
    public function setJeuStrategique(bool $jeu_strategique): static
    {
        $this->jeu_strategique = $jeu_strategique;

        return $this;
    }

    public function getNavigateur(bool $strict = true): ?Personnage
    {
        return $this->navigateur ?? ($strict ? null : $this->getSuzerin($strict));
    }

    public function setNavigateur(?Personnage $navigateur): static
    {
        $this->navigateur = $navigateur;

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
     * Get the value of place_available.
     */
    public function getPlaceAvailable(): ?int
    {
        return $this->place_available ?? 0;
    }

    /**
     * Set the value of place_available.
     */
    public function setPlaceAvailable(?int $place_available): GroupeGn
    {
        $this->place_available = $place_available ?? 0;

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
     * Set the value of sieges.
     */
    public function setSieges(?int $sieges): static
    {
        $this->sieges = $sieges ?? 0;

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
     * Remove Participant entity from collection (one to many).
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }
}
