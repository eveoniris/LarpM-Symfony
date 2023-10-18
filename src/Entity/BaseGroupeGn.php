<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\GroupeGn.
 *
 * @Table(name="groupe_gn", indexes={@Index(name="fk_groupe_gn_groupe1_idx", columns={"groupe_id"}), @Index(name="fk_groupe_gn_gn1_idx", columns={"gn_id"}), @Index(name="fk_groupe_gn_participant1_idx", columns={"responsable_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseGroupeGn", "extended":"GroupeGn"})
 */
class BaseGroupeGn
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
     * @Column(name="`free`", type="boolean")
     */
    protected $free;

    /**
     * @Column(type="string", length=10, nullable=true)
     */
    protected $code;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $jeu_maritime;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $jeu_strategique;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $place_available;

    /**
     * @Column(type="integer", nullable=false)
     */
    protected $agents;

    /**
     * @Column(type="integer", nullable=false)
     */
    protected $bateaux;

    /**
     * @Column(type="integer", nullable=false)
     */
    protected $sieges;

    /**
     * @Column(type="integer", nullable=false)
     */
    protected $initiative;

    /**
     * @OneToMany(targetEntity="Participant", mappedBy="groupeGn")
     *
     * @JoinColumn(name="id", referencedColumnName="groupe_gn_id", nullable=false)
     */
    protected $participants;

    /**
     * @ManyToOne(targetEntity="Groupe", inversedBy="groupeGns")
     *
     * @JoinColumn(name="groupe_id", referencedColumnName="id", nullable=false)
     */
    protected $groupe;

    /**
     * @ManyToOne(targetEntity="Gn", inversedBy="groupeGns")
     *
     * @JoinColumn(name="gn_id", referencedColumnName="id", nullable=false)
     */
    protected $gn;

    /**
     * @ManyToOne(targetEntity="Participant", inversedBy="groupeGns")
     *
     * @JoinColumn(name="responsable_id", referencedColumnName="id")
     */
    protected $participant;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="groupeGns")
     *
     * @JoinColumn(name="suzerain_id", referencedColumnName="id")
     */
    protected $suzerain;

    /**
     * @ManyToMany(targetEntity="GroupeGnOrdre", inversedBy="groupeGns")
     *
     * @JoinTable(name="groupe_gn_ordre",
     *     joinColumns={@JoinColumn(name="groupe_gn_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="id", referencedColumnName="id", nullable=false)}
     * )
     *
     * @OrderBy({"ordre" = "ASC",})
     */
    protected $groupeGnOrdres;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->groupeGnOrdres = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\GroupeGn
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
     * Set the value of free.
     *
     * @param bool $free
     *
     * @return \App\Entity\GroupeGn
     */
    public function setFree($free)
    {
        $this->free = $free;

        return $this;
    }

    /**
     * Get the value of free.
     *
     * @return bool
     */
    public function getFree()
    {
        return $this->free;
    }

    /**
     * Set the value of code.
     *
     * @param string $code
     *
     * @return \App\Entity\GroupeGn
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the value of jeu_maritime.
     *
     * @param bool $jeu_maritime
     *
     * @return \App\Entity\GroupeGn
     */
    public function setJeuMaritime($jeu_maritime)
    {
        $this->jeu_maritime = $jeu_maritime;

        return $this;
    }

    /**
     * Get the value of jeu_maritime.
     *
     * @return bool
     */
    public function getJeuMaritime()
    {
        return $this->jeu_maritime;
    }

    /**
     * Set the value of jeu_strategique.
     *
     * @param bool $jeu_strategique
     *
     * @return \App\Entity\GroupeGn
     */
    public function setJeuStrategique($jeu_strategique)
    {
        $this->jeu_strategique = $jeu_strategique;

        return $this;
    }

    /**
     * Get the value of jeu_strategique.
     *
     * @return bool
     */
    public function getJeuStrategique()
    {
        return $this->jeu_strategique;
    }

    /**
     * Set the value of place_available.
     *
     * @param int $place_available
     *
     * @return \App\Entity\GroupeGn
     */
    public function setPlaceAvailable($place_available)
    {
        $this->place_available = $place_available;

        return $this;
    }

    /**
     * Get the value of place_available.
     *
     * @return int
     */
    public function getPlaceAvailable()
    {
        return $this->place_available;
    }

    /**
     * Set the value of agents.
     *
     * @param int $agents
     *
     * @return \App\Entity\GroupeGn
     */
    public function setAgents($agents)
    {
        $this->agents = $agents;

        return $this;
    }

    /**
     * Get the value of agents.
     *
     * @return int
     */
    public function getAgents()
    {
        return $this->agents;
    }

    /**
     * Set the value of bateaux.
     *
     * @param int $bateaux
     *
     * @return \App\Entity\GroupeGn
     */
    public function setBateaux($bateaux)
    {
        $this->bateaux = $bateaux;

        return $this;
    }

    /**
     * Get the value of bateaux.
     *
     * @return int
     */
    public function getBateaux()
    {
        return $this->bateaux;
    }

    /**
     * Set the value of sieges.
     *
     * @param int $sieges
     *
     * @return \App\Entity\GroupeGn
     */
    public function setSieges($sieges)
    {
        $this->sieges = $sieges;

        return $this;
    }

    /**
     * Get the value of sieges.
     *
     * @return int
     */
    public function getSieges()
    {
        return $this->sieges;
    }

    /**
     * Set the value of initiative.
     *
     * @param int $initiative
     *
     * @return \App\Entity\GroupeGn
     */
    public function setInitiative($initiative)
    {
        $this->initiative = $initiative;

        return $this;
    }

    /**
     * Get the value of initiative.
     *
     * @return int
     */
    public function getInitiative()
    {
        return $this->initiative;
    }

    /**
     * Add Participant entity to collection (one to many).
     *
     * @return \App\Entity\GroupeGn
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Remove Participant entity from collection (one to many).
     *
     * @return \App\Entity\GroupeGn
     */
    public function removeParticipant(Participant $participant)
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Get Participant entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Set Groupe entity (many to one).
     *
     * @return \App\Entity\GroupeGn
     */
    public function setGroupe(Groupe $groupe = null)
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * Get Groupe entity (many to one).
     *
     * @return \App\Entity\Groupe
     */
    public function getGroupe()
    {
        return $this->groupe;
    }

    /**
     * Set Gn entity (many to one).
     *
     * @return \App\Entity\GroupeGn
     */
    public function setGn(Gn $gn = null)
    {
        $this->gn = $gn;

        return $this;
    }

    /**
     * Get Gn entity (many to one).
     *
     * @return \App\Entity\Gn
     */
    public function getGn()
    {
        return $this->gn;
    }

    /**
     * Set Participant entity (many to one).
     *
     * @return \App\Entity\GroupeGn
     */
    public function setParticipant(Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get Participant entity (many to one).
     *
     * @return \App\Entity\Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\GroupeGn
     */
    public function setSuzerain(Personnage $suzerain = null)
    {
        $this->suzerain = $suzerain;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getSuzerain()
    {
        return $this->suzerain;
    }

    /**
     * Add GroupeGnOrdre entity to collection.
     *
     * @return \App\Entity\GroupeGn
     */
    public function addGroupeGnOrdre(GroupeGnOrdre $groupeGnOrdre)
    {
        $groupeGnOrdre->addGroupeGn($this);
        $this->groupeGnOrdres[] = $groupeGnOrdre;

        return $this;
    }

    /**
     * Remove GroupeGnOrdre entity from collection.
     *
     * @return \App\Entity\GroupeGn
     */
    public function removeGroupeGnOrdre(GroupeGnOrdre $groupeGnOrdre)
    {
        $groupeGnOrdre->removeGroupeGn($this);
        $this->groupeGnOrdres->removeElement($groupeGnOrdre);

        return $this;
    }

    /**
     * Get GroupeGnOrdre entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupeGnOrdres()
    {
        return $this->groupeGnOrdres;
    }

    public function __sleep()
    {
        return ['id', 'groupe_id', 'gn_id', 'responsable_id', 'free', 'code', 'jeu_maritime', 'jeu_strategique', 'place_available'];
    }
}