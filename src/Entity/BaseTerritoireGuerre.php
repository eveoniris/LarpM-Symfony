<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\TerritoireGuerre.
 *
 * @Table(name="territoire_guerre")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BaseTerritoireGuerre", "extended":"TerritoireGuerre"})
 */
class BaseTerritoireGuerre
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $territoires;

    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $puissance;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $puissance_max;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $protection;

    /**
     * @OneToOne(targetEntity="Territoire", mappedBy="territoireGuerre")
     */
    protected $territoire;

    public function __construct()
    {
        $this->territoires = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param int $id
     *
     * @return \App\Entity\TerritoireGuerre
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
     * Set the value of puissance.
     *
     * @param int $puissance
     *
     * @return \App\Entity\TerritoireGuerre
     */
    public function setPuissance($puissance)
    {
        $this->puissance = $puissance;

        return $this;
    }

    /**
     * Get the value of puissance.
     *
     * @return int
     */
    public function getPuissance()
    {
        return $this->puissance;
    }

    /**
     * Set the value of puissance_max.
     *
     * @param int $puissance_max
     *
     * @return \App\Entity\TerritoireGuerre
     */
    public function setPuissanceMax($puissance_max)
    {
        $this->puissance_max = $puissance_max;

        return $this;
    }

    /**
     * Get the value of puissance_max.
     *
     * @return int
     */
    public function getPuissanceMax()
    {
        return $this->puissance_max;
    }

    /**
     * Set the value of protection.
     *
     * @param int $protection
     *
     * @return \App\Entity\TerritoireGuerre
     */
    public function setProtection($protection)
    {
        $this->protection = $protection;

        return $this;
    }

    /**
     * Get the value of protection.
     *
     * @return int
     */
    public function getProtection()
    {
        return $this->protection;
    }

    /**
     * Set Territoire entity (one to one).
     *
     * @return \App\Entity\TerritoireGuerre
     */
    public function setTerritoire(Territoire $territoire = null)
    {
        $territoire->setTerritoireGuerre($this);
        $this->territoire = $territoire;

        return $this;
    }

    /**
     * Get Territoire entity (one to one).
     *
     * @return \App\Entity\Territoire
     */
    public function getTerritoire()
    {
        return $this->territoire;
    }

    public function __sleep()
    {
        return ['id', 'puissance', 'puissance_max', 'protection'];
    }
}
