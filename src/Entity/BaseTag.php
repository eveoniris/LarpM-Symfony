<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[ORM\Entity]
#[ORM\Table(name: 'billet')]
#[ORM\Index(columns: ['createur_id'], name: 'fk_billet_user1')]
#[ORM\Index(columns: ['gn_id'], name: 'fk_billet_gn1_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['base' => 'BaseTag', 'extended' => 'Tag'])]
abstract class BaseTag
{
    #[Id, Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true]), GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @Column(type="string", length=100, nullable=true)
     */
    #[Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 100, nullable: true)]
    protected string $nom;

    /**
     * @ManyToMany(targetEntity="Objet", mappedBy="tags")
     */
    #[ORM\ManyToMany(targetEntity: Objet::class, inversedBy: 'tags')]
    protected ArrayCollection $objets;

    public function __construct()
    {
        $this->objets = new ArrayCollection();
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
     * Set the value of nom.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get the value of nom.
     */
    public function getNom(): string
    {
        return $this->nom ?? '';
    }

    /**
     * Add Objet entity to collection.
     */
    public function addObjet(Objet $objet): static
    {
        $this->objets[] = $objet;

        return $this;
    }

    /**
     * Remove Objet entity from collection.
     */
    public function removeObjet(Objet $objet): static
    {
        $this->objets->removeElement($objet);

        return $this;
    }

    /**
     * Get Objet entity collection.
     */
    public function getObjets(): ArrayCollection
    {
        return $this->objets;
    }

    public function __sleep()
    {
        return ['id', 'nom'];
    }
}
