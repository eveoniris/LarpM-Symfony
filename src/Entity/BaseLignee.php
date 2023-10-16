<?php

/**
 * Created by Kevin F.
 */

namespace App\Entity;

/**
 * App\Entity\Lignee.
 *
 * @Table(name="lignees")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseLignee", "extended":"Lignee"})
 */
class BaseLignee
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
     * @Column(type="string")
     */
    protected $nom;

    /**
     * @Column(name="`description`", type="text", nullable=true)
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="PersonnageLignee", mappedBy="lignee")
     *
     * @JoinColumn(name="id", referencedColumnName="lignee_id", nullable=false)
     */
    protected $personnageLignees;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Lignee
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
     * Set the value of nom.
     *
     * @param string $nom
     *
     * @return \App\Entity\Lignee
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set the value of description.
     *
     * @param string $description
     *
     * @return \App\Entity\Lignee
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

    public function getPersonnageLignees()
    {
        return $this->personnageLignees;
    }

    public function setPersonnageLignees(mixed $personnageLignees): void
    {
        $this->personnageLignees = $personnageLignees;
    }

    public function __sleep()
    {
        return ['id', 'nom', 'description'];
    }
}
