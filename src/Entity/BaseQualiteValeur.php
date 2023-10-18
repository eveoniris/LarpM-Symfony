<?php

namespace App\Entity;

/**
 * App\Entity\QualiteValeur.
 *
 * @Table(name="qualite_valeur", indexes={@Index(name="fk_qualite_valeur_qualite1_idx", columns={"qualite_id"}), @Index(name="fk_qualite_valeur_monnaie1_idx", columns={"monnaie_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseQualiteValeur", "extended":"QualiteValeur"})
 */
class BaseQualiteValeur
{
    /**
     * @Id
     *
     * @Column(type="integer")
     */
    protected $qualite_id;

    /**
     * @Id
     *
     * @Column(type="integer")
     */
    protected $monnaie_id;

    /**
     * @Column(type="integer")
     */
    protected $nombre;

    /**
     * @ManyToOne(targetEntity="Qualite", inversedBy="qualiteValeurs")
     *
     * @JoinColumn(name="qualite_id", referencedColumnName="id", nullable=false)
     */
    protected $qualite;

    /**
     * @ManyToOne(targetEntity="Monnaie", inversedBy="qualiteValeurs")
     *
     * @JoinColumn(name="monnaie_id", referencedColumnName="id", nullable=false)
     */
    protected $monnaie;

    public function __construct()
    {
    }

    /**
     * Set the value of qualite_id.
     *
     * @param int $qualite_id
     *
     * @return \App\Entity\QualiteValeur
     */
    public function setQualiteId($qualite_id)
    {
        $this->qualite_id = $qualite_id;

        return $this;
    }

    /**
     * Get the value of qualite_id.
     *
     * @return int
     */
    public function getQualiteId()
    {
        return $this->qualite_id;
    }

    /**
     * Set the value of monnaie_id.
     *
     * @param int $monnaie_id
     *
     * @return \App\Entity\QualiteValeur
     */
    public function setMonnaieId($monnaie_id)
    {
        $this->monnaie_id = $monnaie_id;

        return $this;
    }

    /**
     * Get the value of monnaie_id.
     *
     * @return int
     */
    public function getMonnaieId()
    {
        return $this->monnaie_id;
    }

    /**
     * Set the value of nombre.
     *
     * @param int $nombre
     *
     * @return \App\Entity\QualiteValeur
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get the value of nombre.
     *
     * @return int
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * Set Qualite entity (many to one).
     *
     * @return \App\Entity\QualiteValeur
     */
    public function setQualite(Qualite $qualite = null)
    {
        $this->qualite = $qualite;

        return $this;
    }

    /**
     * Get Qualite entity (many to one).
     *
     * @return \App\Entity\Qualite
     */
    public function getQualite()
    {
        return $this->qualite;
    }

    /**
     * Set Monnaie entity (many to one).
     *
     * @return \App\Entity\QualiteValeur
     */
    public function setMonnaie(Monnaie $monnaie = null)
    {
        $this->monnaie = $monnaie;

        return $this;
    }

    /**
     * Get Monnaie entity (many to one).
     *
     * @return \App\Entity\Monnaie
     */
    public function getMonnaie()
    {
        return $this->monnaie;
    }

    public function __sleep()
    {
        return ['qualite_id', 'monnaie_id', 'nombre'];
    }
}