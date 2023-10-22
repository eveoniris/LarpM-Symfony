<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Priere.
 *
 * @Table(name="priere", indexes={@Index(name="fk_priere_sphere1_idx", columns={"sphere_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePriere", "extended":"Priere"})
 */
class BasePriere
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
     * @Column(type="text", nullable=true)
     */
    protected $annonce;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $documentUrl;

    /**
     * @Column(type="integer")
     */
    protected $niveau;

    /**
     * @ManyToOne(targetEntity="Sphere", inversedBy="prieres")
     *
     * @JoinColumn(name="sphere_id", referencedColumnName="id", nullable=false)
     */
    protected $sphere;

    /**
     * @ManyToMany(targetEntity="Personnage", inversedBy="prieres")
     *
     * @JoinTable(name="personnages_prieres",
     *     joinColumns={@JoinColumn(name="priere_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)}
     * )
     */
    protected $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Priere
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
     * @return \App\Entity\Priere
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
     * @return \App\Entity\Priere
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
     * Set the value of annonce.
     *
     * @param string $annonce
     *
     * @return \App\Entity\Priere
     */
    public function setAnnonce($annonce)
    {
        $this->annonce = $annonce;

        return $this;
    }

    /**
     * Get the value of annonce.
     *
     * @return string
     */
    public function getAnnonce()
    {
        return $this->annonce;
    }

    /**
     * Set the value of documentUrl.
     *
     * @param string $documentUrl
     *
     * @return \App\Entity\Priere
     */
    public function setDocumentUrl($documentUrl)
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get the value of documentUrl.
     *
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    /**
     * Set the value of niveau.
     *
     * @param int $niveau
     *
     * @return \App\Entity\Priere
     */
    public function setNiveau($niveau)
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * Get the value of niveau.
     *
     * @return int
     */
    public function getNiveau()
    {
        return $this->niveau;
    }

    /**
     * Set Sphere entity (many to one).
     *
     * @return \App\Entity\Priere
     */
    public function setSphere(Sphere $sphere = null)
    {
        $this->sphere = $sphere;

        return $this;
    }

    /**
     * Get Sphere entity (many to one).
     *
     * @return \App\Entity\Sphere
     */
    public function getSphere()
    {
        return $this->sphere;
    }

    /**
     * Add Personnage entity to collection.
     *
     * @return \App\Entity\Priere
     */
    public function addPersonnage(Personnage $personnage)
    {
        $personnage->addPriere($this);
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     *
     * @return \App\Entity\Priere
     */
    public function removePersonnage(Personnage $personnage)
    {
        $personnage->removePriere($this);
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

    public function __sleep()
    {
        return ['id', 'label', 'description', 'annonce', 'documentUrl', 'niveau', 'sphere_id'];
    }
}
