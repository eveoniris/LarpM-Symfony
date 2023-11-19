<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Sphere.
 *
 * @Table(name="sphere")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseSphere", "extended":"Sphere"})
 */
class BaseSphere
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=45, nullable=true)
     */
    protected $label;

    /**
     * @OneToMany(targetEntity="Priere", mappedBy="sphere")
     *
     * @JoinColumn(name="id", referencedColumnName="sphere_id", nullable=false)
     */
    protected $prieres;

    /**
     * @ManyToMany(targetEntity="Religion", inversedBy="spheres")
     *
     * @JoinTable(name="religions_spheres",
     *     joinColumns={@JoinColumn(name="sphere_id", referencedColumnName="id", nullable=false)},
     *     inverseJoinColumns={@JoinColumn(name="religion_id", referencedColumnName="id", nullable=false)}
     * )
     *
     * @OrderBy({"label" = "ASC",})
     */
    protected $religions;

    public function __construct()
    {
        $this->prieres = new ArrayCollection();
        $this->religions = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\Sphere
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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of label.
     *
     * @param string $label
     *
     * @return \App\Entity\Sphere
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    /**
     * Add Priere entity to collection (one to many).
     *
     * @return \App\Entity\Sphere
     */
    public function addPriere(Priere $priere)
    {
        $this->prieres[] = $priere;

        return $this;
    }

    /**
     * Remove Priere entity from collection (one to many).
     *
     * @return \App\Entity\Sphere
     */
    public function removePriere(Priere $priere)
    {
        $this->prieres->removeElement($priere);

        return $this;
    }

    /**
     * Get Priere entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrieres()
    {
        return $this->prieres;
    }

    /**
     * Add Religion entity to collection.
     *
     * @return \App\Entity\Sphere
     */
    public function addReligion(Religion $religion)
    {
        $religion->addSphere($this);
        $this->religions[] = $religion;

        return $this;
    }

    /**
     * Remove Religion entity from collection.
     *
     * @return \App\Entity\Sphere
     */
    public function removeReligion(Religion $religion)
    {
        $religion->removeSphere($this);
        $this->religions->removeElement($religion);

        return $this;
    }

    /**
     * Get Religion entity collection.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReligions()
    {
        return $this->religions;
    }

    public function __sleep()
    {
        return ['id', 'label'];
    }
}
