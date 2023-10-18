<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Domaine.
 *
 * @Table(name="domaine")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseDomaine", "extended":"Domaine"})
 */
class BaseDomaine
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
     * @Column(type="string", length=45)
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="Sort", mappedBy="domaine")
     *
     * @JoinColumn(name="id", referencedColumnName="domaine_id", nullable=false)
     *
     * @OrderBy({"label" = "ASC", "niveau" = "ASC",})
     */
    protected $sorts;

    /**
     * @ManyToMany(targetEntity="Personnage", mappedBy="domaines")
     */
    protected $personnages;

    public function __construct()
    {
        $this->sorts = new ArrayCollection();
        $this->personnages = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Domaine
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
     * @return \App\Entity\Domaine
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
     * @return \App\Entity\Domaine
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
     * Add Sort entity to collection (one to many).
     *
     * @return \App\Entity\Domaine
     */
    public function addSort(Sort $sort)
    {
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * Remove Sort entity from collection (one to many).
     *
     * @return \App\Entity\Domaine
     */
    public function removeSort(Sort $sort)
    {
        $this->sorts->removeElement($sort);

        return $this;
    }

    /**
     * Get Sort entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * Add Personnage entity to collection.
     *
     * @return \App\Entity\Domaine
     */
    public function addPersonnage(Personnage $personnage)
    {
        $this->personnages[] = $personnage;

        return $this;
    }

    /**
     * Remove Personnage entity from collection.
     *
     * @return \App\Entity\Domaine
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

    public function __sleep()
    {
        return ['id', 'label', 'description'];
    }
}