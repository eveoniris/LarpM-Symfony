<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Localisation extends BaseLocalisation
{
    /**
     * Retourne la liste de tous les objets dans cette localisation.
     */
    /** @return Collection<int, Objet> */
    public function objets(): Collection
    {
        $objets = [];
        foreach ($this->getRangements() as $rangement) {
            $objets = [...$objets, ...$rangement->getObjets()->toArray()];
        }

        return new ArrayCollection($objets);
    }
}
