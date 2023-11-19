<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * App\Entity\Photo
 *
 * @Table(name="photo")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base":"BasePhoto", "extended":"Photo"})
 */
class BasePhoto
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(name="`name`", type="string", length=45)
     */
    protected $name;

    /**
     * @Column(type="string", length=45)
     */
    protected $extension;

    /**
     * @Column(type="string", length=45)
     */
    protected $real_name;

    /**
     * @Column(name="`data`", type="blob", nullable=true)
     */
    protected $data;

    /**
     * @Column(type="datetime")
     */
    protected $creation_date;

    /**
     * @Column(type="string", length=100)
     */
    protected $filename;

    /**
     * @OneToMany(targetEntity="Objet", mappedBy="photo", cascade={"persist", "merge", "remove", "detach", "all"})
     * @JoinColumn(name="id", referencedColumnName="photo_id", nullable=false, onDelete="CASCADE")
     */
    protected $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
    }

    /**
     * Set the value of id.
     *
     * @param integer $id
     * @return \App\Entity\Photo
     */
    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of name.
     *
     * @param string $name
     * @return \App\Entity\Photo
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of extension.
     *
     * @param string $extension
     * @return \App\Entity\Photo
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the value of extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set the value of real_name.
     *
     * @param string $real_name
     * @return \App\Entity\Photo
     */
    public function setRealName($real_name)
    {
        $this->real_name = $real_name;

        return $this;
    }

    /**
     * Get the value of real_name.
     *
     * @return string
     */
    public function getRealName()
    {
        return $this->real_name;
    }

    /**
     * Set the value of data.
     *
     * @param string $data
     * @return \App\Entity\Photo
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of creation_date.
     *
     * @param \DateTime $creation_date
     * @return \App\Entity\Photo
     */
    public function setCreationDate($creation_date)
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Set the value of filename.
     *
     * @param string $filename
     * @return \App\Entity\Photo
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the value of filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Add Objet entity to collection (one to many).
     *
     * @return \App\Entity\Photo
     */
    public function addObjet(Objet $objet)
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     *
     * @return \App\Entity\Photo
     */
    public function removeObjet(Objet $objet)
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getObjets()
    {
        return $this->objets;
    }

    public function __sleep()
    {
        return array('id', 'name', 'extension', 'real_name', 'data', 'creation_date', 'filename');
    }
}
