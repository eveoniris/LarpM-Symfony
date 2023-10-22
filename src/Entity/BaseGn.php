<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Gn.
 *
 * @Table(name="gn", indexes={@Index(name="fk_gn_topic1_idx", columns={"topic_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseGn", "extended":"Gn"})
 */
class BaseGn
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $xp_creation;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $date_jeu;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $date_debut;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $date_fin;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $date_installation_joueur;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $date_fin_orga;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $adresse;

    /**
     * @Column(type="boolean")
     */
    protected $actif;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $billetterie;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $conditions_inscription;

    /**
     * @OneToMany(targetEntity="Annonce", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $annonces;

    /**
     * @OneToMany(targetEntity="Background", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $backgrounds;

    /**
     * @OneToMany(targetEntity="Billet", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $billets;

    /**
     * @OneToMany(targetEntity="Debriefing", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $debriefings;

    /**
     * @OneToMany(targetEntity="GroupeGn", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $groupeGns;

    /**
     * @OneToMany(targetEntity="Participant", mappedBy="gn", cascade={"persist"})
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $participants;

    /**
     * @OneToMany(targetEntity="PersonnageBackground", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $personnageBackgrounds;

    /**
     * @OneToMany(targetEntity="Rumeur", mappedBy="gn")
     *
     * @JoinColumn(name="id", referencedColumnName="gn_id", nullable=false)
     */
    protected $rumeurs;

    /**
     * @ManyToOne(targetEntity="Topic", inversedBy="gns", cascade={"persist"})
     *
     * @JoinColumn(name="topic_id", referencedColumnName="id", nullable=false)
     */
    protected $topic;

    public function __construct()
    {
        $this->annonces = new ArrayCollection();
        $this->backgrounds = new ArrayCollection();
        $this->billets = new ArrayCollection();
        $this->debriefings = new ArrayCollection();
        $this->groupeGns = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->personnageBackgrounds = new ArrayCollection();
        $this->rumeurs = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Gn
     */
    public function setId($id): static
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
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Gn
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of xp_creation.
     *
     * @param int $xp_creation
     *
     * @return \App\Entity\Gn
     */
    public function setXpCreation($xp_creation)
    {
        $this->xp_creation = $xp_creation;

        return $this;
    }

    /**
     * Get the value of xp_creation.
     *
     * @return int
     */
    public function getXpCreation()
    {
        return $this->xp_creation;
    }

    /**
     * Set the value of date_jeu.
     *
     * @param int $date_jeu
     *
     * @return \App\Entity\Gn
     */
    public function setDateJeu($date_jeu)
    {
        $this->date_jeu = $date_jeu;

        return $this;
    }

    /**
     * Get the value of date_jeu.
     *
     * @return int
     */
    public function getDateJeu()
    {
        return $this->date_jeu;
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Gn
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of date_debut.
     *
     * @param \DateTime $date_debut
     *
     * @return \App\Entity\Gn
     */
    public function setDateDebut($date_debut)
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    /**
     * Get the value of date_debut.
     *
     * @return \DateTime
     */
    public function getDateDebut()
    {
        return $this->date_debut;
    }

    /**
     * Set the value of date_fin.
     *
     * @param \DateTime $date_fin
     *
     * @return \App\Entity\Gn
     */
    public function setDateFin($date_fin)
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    /**
     * Get the value of date_fin.
     *
     * @return \DateTime
     */
    public function getDateFin()
    {
        return $this->date_fin;
    }

    /**
     * Set the value of date_installation_joueur.
     *
     * @param \DateTime $date_installation_joueur
     *
     * @return \App\Entity\Gn
     */
    public function setDateInstallationJoueur($date_installation_joueur)
    {
        $this->date_installation_joueur = $date_installation_joueur;

        return $this;
    }

    /**
     * Get the value of date_installation_joueur.
     *
     * @return \DateTime
     */
    public function getDateInstallationJoueur()
    {
        return $this->date_installation_joueur;
    }

    /**
     * Set the value of date_fin_orga.
     *
     * @param \DateTime $date_fin_orga
     *
     * @return \App\Entity\Gn
     */
    public function setDateFinOrga($date_fin_orga)
    {
        $this->date_fin_orga = $date_fin_orga;

        return $this;
    }

    /**
     * Get the value of date_fin_orga.
     *
     * @return \DateTime
     */
    public function getDateFinOrga()
    {
        return $this->date_fin_orga;
    }

    /**
     * Set the value of adresse.
     *
     * @param string $adresse
     *
     * @return \App\Entity\Gn
     */
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get the value of adresse.
     *
     * @return string
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * Set the value of actif.
     *
     * @param bool $actif
     *
     * @return \App\Entity\Gn
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * Get the value of actif.
     *
     * @return bool
     */
    public function getActif()
    {
        return $this->actif;
    }

    /**
     * Set the value of billetterie.
     *
     * @param string $billetterie
     *
     * @return \App\Entity\Gn
     */
    public function setBilletterie($billetterie)
    {
        $this->billetterie = $billetterie;

        return $this;
    }

    /**
     * Get the value of billetterie.
     *
     * @return string
     */
    public function getBilletterie()
    {
        return $this->billetterie;
    }

    /**
     * Set the value of conditions_inscription.
     *
     * @param string $conditions_inscription
     *
     * @return \App\Entity\Gn
     */
    public function setConditionsInscription($conditions_inscription)
    {
        $this->conditions_inscription = $conditions_inscription;

        return $this;
    }

    /**
     * Get the value of conditions_inscription.
     *
     * @return string
     */
    public function getConditionsInscription()
    {
        return $this->conditions_inscription;
    }

    /**
     * Add Annonce entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addAnnonce(Annonce $annonce)
    {
        $this->annonces[] = $annonce;

        return $this;
    }

    /**
     * Remove Annonce entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeAnnonce(Annonce $annonce)
    {
        $this->annonces->removeElement($annonce);

        return $this;
    }

    /**
     * Get Annonce entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnnonces()
    {
        return $this->annonces;
    }

    /**
     * Add Background entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addBackground(Background $background)
    {
        $this->backgrounds[] = $background;

        return $this;
    }

    /**
     * Remove Background entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeBackground(Background $background)
    {
        $this->backgrounds->removeElement($background);

        return $this;
    }

    /**
     * Get Background entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBackgrounds()
    {
        return $this->backgrounds;
    }

    /**
     * Add Billet entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addBillet(Billet $billet)
    {
        $this->billets[] = $billet;

        return $this;
    }

    /**
     * Remove Billet entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeBillet(Billet $billet)
    {
        $this->billets->removeElement($billet);

        return $this;
    }

    /**
     * Get Billet entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBillets()
    {
        return $this->billets;
    }

    /**
     * Add Debriefing entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addDebriefing(Debriefing $debriefing)
    {
        $this->debriefings[] = $debriefing;

        return $this;
    }

    /**
     * Remove Debriefing entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeDebriefing(Debriefing $debriefing)
    {
        $this->debriefings->removeElement($debriefing);

        return $this;
    }

    /**
     * Get Debriefing entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDebriefings()
    {
        return $this->debriefings;
    }

    /**
     * Add GroupeGn entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addGroupeGn(GroupeGn $groupeGn)
    {
        $this->groupeGns[] = $groupeGn;

        return $this;
    }

    /**
     * Remove GroupeGn entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeGroupeGn(GroupeGn $groupeGn)
    {
        $this->groupeGns->removeElement($groupeGn);

        return $this;
    }

    /**
     * Get GroupeGn entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupeGns()
    {
        return $this->groupeGns;
    }

    /**
     * Add Participant entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Remove Participant entity from collection (one to many).
     *
     * @return \App\Entity\Gn
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
     * Add PersonnageBackground entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addPersonnageBackground(PersonnageBackground $personnageBackground)
    {
        $this->personnageBackgrounds[] = $personnageBackground;

        return $this;
    }

    /**
     * Remove PersonnageBackground entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removePersonnageBackground(PersonnageBackground $personnageBackground)
    {
        $this->personnageBackgrounds->removeElement($personnageBackground);

        return $this;
    }

    /**
     * Get PersonnageBackground entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnageBackgrounds()
    {
        return $this->personnageBackgrounds;
    }

    /**
     * Add Rumeur entity to collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function addRumeur(Rumeur $rumeur)
    {
        $this->rumeurs[] = $rumeur;

        return $this;
    }

    /**
     * Remove Rumeur entity from collection (one to many).
     *
     * @return \App\Entity\Gn
     */
    public function removeRumeur(Rumeur $rumeur)
    {
        $this->rumeurs->removeElement($rumeur);

        return $this;
    }

    /**
     * Get Rumeur entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRumeurs()
    {
        return $this->rumeurs;
    }

    /**
     * Set Topic entity (many to one).
     *
     * @return \App\Entity\Gn
     */
    public function setTopic(Topic $topic = null)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get Topic entity (many to one).
     *
     * @return \App\Entity\Topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    public function __sleep()
    {
        return ['id', 'label', 'xp_creation', 'description', 'date_debut', 'date_fin', 'date_installation_joueur', 'date_fin_orga', 'adresse', 'topic_id', 'actif', 'billetterie', 'conditions_inscription'];
    }
}
