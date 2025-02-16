<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * App\Entity\Photo.
 *
 * @Table(name="photo")
 *
 * @InheritanceType("SINGLE_TABLE")
 *
 * @DiscriminatorColumn(name="discr", type="string")
 *
 * @DiscriminatorMap({"base":"BasePhoto", "extended":"Photo"})
 */
#[ORM\Entity]
#[ORM\Table(name: 'photo')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BasePhoto', 'extended' => 'Photo'])]
abstract class BasePhoto
{
    #[Id, Column(type: Types::INTEGER, ), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $name = null;

    #[Column(type: Types::STRING, length: 45, nullable: true)]
    protected string $extension = '';

    #[Column(name: 'real_name', type: Types::STRING, length: 45)]
    protected string $real_name = '';

    #[Column(name: 'data', type: Types::BLOB, nullable: true)]
    /* Stream Ressource */ protected $data;

    #[Column(name: 'creation_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected \DateTime $creation_date;

    #[Column(name: 'filename', type: Types::STRING, length: 100, nullable: true)]
    protected string $filename = '';

    #[OneToMany(mappedBy: 'photo', targetEntity: Objet::class, cascade: ['persist', 'remove', 'detach', 'all'])]
    #[JoinColumn(name: 'id', referencedColumnName: 'photo_id', nullable: 'false', onDelete: 'CASCADE')]
    protected Collection $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
        $this->creation_date = new \DateTime();
    }

    /**
     * Set the value of id.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of name.
     */
    public function getName(): string
    {
        return $this->name = '';
    }

    /**
     * Set the value of extension.
     */
    public function setExtension(string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the value of extension.
     */
    public function getExtension(): string
    {
        return $this->extension ?? '';
    }

    /**
     * Set the value of real_name.
     */
    public function setRealName(string $real_name): static
    {
        $this->real_name = $real_name;

        return $this;
    }

    /**
     * Get the value of real_name.
     */
    public function getRealName(): string
    {
        return $this->real_name ?? '';
    }

    /**
     * Set the value of data.
     */
    public function setData(/* Stream Ressource */ $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of data.
     */
    public function getData() /* Stream Ressource */
    {
        return $this->data;
    }

    /**
     * Set the value of creation_date.
     */
    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of creation_date.
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of filename.
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the value of filename.
     */
    public function getFilename(): string
    {
        return $this->filename ?? '';
    }

    /**
     * Add Objet entity to collection (one to many).
     */
    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection (one to many).
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection (one to many).
     */
    public function getObjets(): Collection
    {
        return $this->objets;
    }

    /* public function __sleep()
    {
        return ['id', 'name', 'extension', 'real_name', 'data', 'creation_date', 'filename'];
    } */
}
