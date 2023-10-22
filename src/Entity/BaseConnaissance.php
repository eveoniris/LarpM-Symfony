<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Connaissance.
 *
 * @Table(name="connaissance")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseConnaissance", "extended":"Connaissance"})
 */
class BaseConnaissance
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
    protected $contraintes;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $documentUrl;

    /**
     * @Column(type="integer")
     */
    protected $niveau;

    /**
     * @Column(type="boolean", nullable=false, options={"default":0})
     */
    protected $secret;

    /**
     * @ManyToMany(targetEntity="Personnage", mappedBy="connaissances")
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
     * @return \App\Entity\Connaissance
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
     * @return \App\Entity\Connaissance
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
     * @return \App\Entity\Connaissance
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
     * Set the value of contraintes.
     *
     * @param string $contraintes
     *
     * @return \App\Entity\Connaissance
     */
    public function setContraintes($contraintes)
    {
        $this->contraintes = $contraintes;

        return $this;
    }

    /**
     * Get the value of contraintes.
     *
     * @return string
     */
    public function getContraintes()
    {
        return $this->contraintes;
    }

    /**
     * Set the value of documentUrl.
     *
     * @param string $documentUrl
     *
     * @return \App\Entity\Connaissance
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
     * Add Personnage entity to collection.
     *
     * @return \App\Entity\Connaissance
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     *
     * @return \App\Entity\Connaissance
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
     * Set the value of niveau.
     *
     * @param int $niveau
     *
     * @return \App\Entity\Connaissance
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
     * Set the value of secret.
     *
     * @param bool $secret
     *
     * @return \App\Entity\Connaissance
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the value of secret.
     *
     * @return bool
     */
    public function getSecret()
    {
        return $this->secret;
    }

    public function __sleep()
    {
        return ['id', 'label', 'description', 'documentUrl', 'niveau', 'secret'];
    }
}
