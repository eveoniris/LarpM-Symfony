<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\GroupeLangue.
 *
 * @Table(name="groupe_langue")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseGroupeLangue", "extended":"GroupeLangue"})
 */
class BaseGroupeLangue
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=100)
     */
    protected $label;

    /**
     * @Column(type="string", length=45)
     */
    protected $couleur;

    /**
     * @OneToMany(targetEntity="Langue", mappedBy="groupeLangue")
     *
     * @JoinColumn(name="id", referencedColumnName="groupe_langue_id", nullable=false)
     */
    protected $langues;

    public function __construct()
    {
        $this->langues = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\GroupeLangue
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
     * @return \App\Entity\GroupeLangue
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
     * Set the value of couleur.
     *
     * @param string $couleur
     *
     * @return \App\Entity\GroupeLangue
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
     * Add Langue entity to collection (one to many).
     *
     * @return \App\Entity\GroupeLangue
     */
    public function addLangue(Langue $langue)
    {
        $this->langues[] = $langue;

        return $this;
    }

    /**
     * Remove Langue entity from collection (one to many).
     *
     * @return \App\Entity\GroupeLangue
     */
    public function removeLangue(Langue $langue)
    {
        $this->langues->removeElement($langue);

        return $this;
    }

    /**
     * Get Langue entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLangues()
    {
        return $this->langues;
    }

    public function __sleep()
    {
        return ['id', 'label', 'couleur'];
    }
}
