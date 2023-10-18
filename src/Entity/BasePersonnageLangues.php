<?php

namespace App\Entity;

/**
 * App\Entity\PersonnageLangues.
 *
 * @Table(name="personnage_langues", indexes={@Index(name="fk_personnage_langues_personnage1_idx", columns={"personnage_id"}), @Index(name="fk_personnage_langues_langue1_idx", columns={"langue_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePersonnageLangues", "extended":"PersonnageLangues"})
 */
class BasePersonnageLangues
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
     * @Column(name="`source`", type="string", length=45)
     */
    protected $source;

    /**
     * @ManyToOne(targetEntity="Personnage", inversedBy="personnageLangues")
     *
     * @JoinColumn(name="personnage_id", referencedColumnName="id", nullable=false)
     */
    protected $personnage;

    /**
     * @ManyToOne(targetEntity="Langue", inversedBy="personnageLangues")
     *
     * @JoinColumn(name="langue_id", referencedColumnName="id", nullable=false)
     *
     * @OrderBy({"secret" = "ASC", "label" = "ASC"})
     */
    protected $langue;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\PersonnageLangues
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
     * Set the value of source.
     *
     * @param string $source
     *
     * @return \App\Entity\PersonnageLangues
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get the value of source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set Personnage entity (many to one).
     *
     * @return \App\Entity\PersonnageLangues
     */
    public function setPersonnage(Personnage $personnage = null)
    {
        $this->personnage = $personnage;

        return $this;
    }

    /**
     * Get Personnage entity (many to one).
     *
     * @return \App\Entity\Personnage
     */
    public function getPersonnage()
    {
        return $this->personnage;
    }

    /**
     * Set Langue entity (many to one).
     *
     * @return \App\Entity\PersonnageLangues
     */
    public function setLangue(Langue $langue = null)
    {
        $this->langue = $langue;

        return $this;
    }

    /**
     * Get Langue entity (many to one).
     *
     * @return \App\Entity\Langue
     */
    public function getLangue()
    {
        return $this->langue;
    }

    public function __sleep()
    {
        return ['id', 'personnage_id', 'langue_id', 'source'];
    }
}