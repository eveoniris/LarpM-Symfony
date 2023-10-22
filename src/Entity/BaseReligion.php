<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Religion.
 *
 * @Table(name="religion", indexes={@Index(name="fk_religion_topic1_idx", columns={"topic_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseReligion", "extended":"Religion"})
 */
class BaseReligion
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $blason;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_orga;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_fervent;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_pratiquant;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description_fanatique;

    /**
     * @Column(type="boolean", nullable=false, options={"default":0})
     */
    protected $secret;

    /**
     * @OneToMany(targetEntity="PersonnagesReligions", mappedBy="religion")
     *
     * @JoinColumn(name="id", referencedColumnName="religion_id", nullable=false)
     */
    protected $personnagesReligions;

    /**
     * @OneToMany(targetEntity="ReligionDescription", mappedBy="religion")
     *
     * @JoinColumn(name="id", referencedColumnName="religion_id", nullable=false)
     */
    protected $religionDescriptions;

    /**
     * @OneToMany(targetEntity="Territoire", mappedBy="religion")
     *
     * @JoinColumn(name="id", referencedColumnName="religion_id", nullable=false)
     */
    protected $territoires;

    /**
     * @ManyToOne(targetEntity="Topic", inversedBy="religions")
     *
     * @JoinColumn(name="topic_id", referencedColumnName="id", nullable=false)
     */
    protected $topic;

    /**
     * @ManyToMany(targetEntity="Personnage", mappedBy="religions")
     */
    protected $personnages;

    /**
     * @ManyToMany(targetEntity="Sphere", mappedBy="religions")
     */
    protected $spheres;

    public function __construct()
    {
        $this->personnagesReligions = new ArrayCollection();
        $this->religionDescriptions = new ArrayCollection();
        $this->territoires = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->spheres = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Religion
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
     * @return \App\Entity\Religion
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
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Religion
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
     * Set the value of blason.
     *
     * @param string $blason
     *
     * @return \App\Entity\Religion
     */
    public function setBlason($blason)
    {
        $this->blason = $blason;

        return $this;
    }

    /**
     * Get the value of blason.
     *
     * @return string
     */
    public function getBlason()
    {
        return $this->blason;
    }

    /**
     * Set the value of description_orga.
     *
     * @param string $description_orga
     *
     * @return \App\Entity\Religion
     */
    public function setDescriptionOrga($description_orga)
    {
        $this->description_orga = $description_orga;

        return $this;
    }

    /**
     * Get the value of description_orga.
     *
     * @return string
     */
    public function getDescriptionOrga()
    {
        return $this->description_orga;
    }

    /**
     * Set the value of description_fervent.
     *
     * @param string $description_fervent
     *
     * @return \App\Entity\Religion
     */
    public function setDescriptionFervent($description_fervent)
    {
        $this->description_fervent = $description_fervent;

        return $this;
    }

    /**
     * Get the value of description_fervent.
     *
     * @return string
     */
    public function getDescriptionFervent()
    {
        return $this->description_fervent;
    }

    /**
     * Set the value of description_pratiquant.
     *
     * @param string $description_pratiquant
     *
     * @return \App\Entity\Religion
     */
    public function setDescriptionPratiquant($description_pratiquant)
    {
        $this->description_pratiquant = $description_pratiquant;

        return $this;
    }

    /**
     * Get the value of description_pratiquant.
     *
     * @return string
     */
    public function getDescriptionPratiquant()
    {
        return $this->description_pratiquant;
    }

    /**
     * Set the value of description_fanatique.
     *
     * @param string $description_fanatique
     *
     * @return \App\Entity\Religion
     */
    public function setDescriptionFanatique($description_fanatique)
    {
        $this->description_fanatique = $description_fanatique;

        return $this;
    }

    /**
     * Get the value of description_fanatique.
     *
     * @return string
     */
    public function getDescriptionFanatique()
    {
        return $this->description_fanatique;
    }

    /**
     * Set the value of secret.
     *
     * @param int $secret
     *
     * @return \App\Entity\Religion
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     *
     * @return int
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Add PersonnagesReligions entity to collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function addPersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions[] = $personnagesReligions;

        return $this;
    }

    /**
     * Remove PersonnagesReligions entity from collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function removePersonnagesReligions(PersonnagesReligions $personnagesReligions)
    {
        $this->personnagesReligions->removeElement($personnagesReligions);

        return $this;
    }

    /**
     * Get PersonnagesReligions entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnagesReligions()
    {
        return $this->personnagesReligions;
    }

    /**
     * Add ReligionDescription entity to collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function addReligionDescription(ReligionDescription $religionDescription)
    {
        $this->religionDescriptions[] = $religionDescription;

        return $this;
    }

    /**
     * Remove ReligionDescription entity from collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function removeReligionDescription(ReligionDescription $religionDescription)
    {
        $this->religionDescriptions->removeElement($religionDescription);

        return $this;
    }

    /**
     * Get ReligionDescription entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReligionDescriptions()
    {
        return $this->religionDescriptions;
    }

    /**
     * Add Territoire entity to collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function addTerritoire(Territoire $territoire)
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection (one to many).
     *
     * @return \App\Entity\Religion
     */
    public function removeTerritoire(Territoire $territoire)
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoires()
    {
        return $this->territoires;
    }

    /**
     * Set Topic entity (many to one).
     *
     * @return \App\Entity\Religion
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

    /**
     * Add Personnage entity to collection.
     *
     * @return \App\Entity\Religion
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     *
     * @return \App\Entity\Religion
     */
    public function removePersonnage(Personnage $personnage)
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * Get Personnage entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonnages()
    {
        return $this->personnages;
    }

    /**
     * Add Sphere entity to collection.
     *
     * @return \App\Entity\Religion
     */
    public function addSphere(Sphere $sphere)
    {
        $this->spheres[] = $sphere;

        return $this;
    }

    /**
     * Remove Sphere entity from collection.
     *
     * @return \App\Entity\Religion
     */
    public function removeSphere(Sphere $sphere)
    {
        $this->spheres->removeElement($sphere);

        return $this;
    }

    /**
     * Get Sphere entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpheres()
    {
        return $this->spheres;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'topic_id', 'blason', 'description_orga', 'description_fervent', 'description_pratiquant', 'description_fanatique', 'secret'];
    }
}
