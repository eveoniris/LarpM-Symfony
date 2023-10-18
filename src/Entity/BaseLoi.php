<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Loi.
 *
 * @Table(name="loi")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseLoi", "extended":"Loi"})
 */
class BaseLoi
{
    /**
     * @Id
     *
     * @Column(type="integer", options={"unsigned":true})
     *
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $label;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $documentUrl;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ManyToMany(targetEntity="Territoire", mappedBy="lois")
     */
    protected $territoires;

    public function __construct()
    {
        $this->territoires = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Loi
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
     * @return \App\Entity\Loi
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
     * Set the value of documentUrl.
     *
     * @param string $documentUrl
     *
     * @return \App\Entity\Loi
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
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Loi
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
     * Add Territoire entity to collection.
     *
     * @return \App\Entity\Loi
     */
    public function addTerritoire(Territoire $territoire)
    {
        $this->territoires[] = $territoire;

        return $this;
    }

    /**
     * Remove Territoire entity from collection.
     *
     * @return \App\Entity\Loi
     */
    public function removeTerritoire(Territoire $territoire)
    {
        $this->territoires->removeElement($territoire);

        return $this;
    }

    /**
     * Get Territoire entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoires()
    {
        return $this->territoires;
    }

    public function __sleep()
    {
        return ['id', 'label', 'documentUrl', 'description'];
    }
}