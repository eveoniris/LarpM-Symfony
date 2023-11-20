<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\ObjetCarac.
 *
 * @Table(name="objet_carac", indexes={@Index(name="fk_objet_carac_objet1_idx", columns={"objet_id"})})
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseObjetCarac", "extended":"ObjetCarac"})
 */
class BaseObjetCarac
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $taille;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $poid;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $couleur;

    /**
     * @OneToOne(targetEntity="Objet", inversedBy="objetCarac", cascade={"persist", "merge", "remove", "detach", "all"})
     *
     * @JoinColumn(name="objet_id", referencedColumnName="id", nullable=false)
     */
    protected $objet;

    public function __construct()
    {
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\ObjetCarac
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of taille.
     *
     * @param string $taille
     *
     * @return \App\Entity\ObjetCarac
     */
    public function setTaille($taille)
    {
        $this->taille = $taille;

        return $this;
    }

    /**
     * Get the value of taille.
     *
     * @return string
     */
    public function getTaille()
    {
        return $this->taille;
    }

    /**
     * Set the value of poid.
     *
     * @param string $poid
     *
     * @return \App\Entity\ObjetCarac
     */
    public function setPoid($poid)
    {
        $this->poid = $poid;

        return $this;
    }

    /**
     * Get the value of poid.
     *
     * @return string
     */
    public function getPoid()
    {
        return $this->poid;
    }

    /**
     * Set the value of couleur.
     *
     * @param string $couleur
     *
     * @return \App\Entity\ObjetCarac
     */
    public function setCouleur($couleur)
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get the value of couleur.
     *
     * @return string
     */
    public function getCouleur()
    {
        return $this->couleur;
    }

    /**
     * Set Objet entity (one to one).
     *
     * @return \App\Entity\ObjetCarac
     */
    public function setObjet(Objet $objet)
    {
        $this->objet = $objet;

        return $this;
    }

    /**
     * Get Objet entity (one to one).
     *
     * @return \App\Entity\Objet
     */
    public function getObjet()
    {
        return $this->objet;
    }

    public function __sleep()
    {
        return ['id', 'objet_id', 'taille', 'poid', 'couleur'];
    }
}
