<?php

namespace App\Entity;

use App\Repository\ReligionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ReligionRepository::class)]
class Religion extends BaseReligion implements \Stringable
{
    /**
     * @ManyToMany(targetEntity="Territoire", mappedBy="religions")
     */
    protected $territoireSecondaires;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->territoireSecondaires = new ArrayCollection();
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    /**
     * Fourni la liste des territoires ou la religion est une religion secondaire.
     */
    public function getTerritoireSecondaires()
    {
        return $this->territoireSecondaires;
    }

    /**
     * Ajoute un territoire dans la liste des territoires ou la religion est une religion secondaire.
     */
    public function addTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires[] = $territoire;

        return $this;
    }

    /**
     * Retire un territoire de la liste des territoires ou la religion est une religion secondaire.
     */
    public function removeTerritoireSecondaire(Territoire $territoire): static
    {
        $this->territoireSecondaires->removeElement($territoire);

        return $this;
    }

    /**
     * Fourni la liste des territoires ou la religion est la religion principale.
     */
    public function getTerritoirePrincipaux()
    {
        return $this->getTerritoires();
    }
}
