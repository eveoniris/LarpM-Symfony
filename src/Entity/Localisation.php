<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Localisation extends BaseLocalisation
{
    /**
     * Retourne la liste de tous les objets dans cette localisation.
     */
    public function objets(): ArrayCollection
    {
        $objets = [];
        foreach ($this->getRangements() as $rangement) {
            $objets = [...$objets, ...$rangement->getObjets()->toArray()];
        }

        return new ArrayCollection($objets);
    }
}
