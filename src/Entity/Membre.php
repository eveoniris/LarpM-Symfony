<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Membre extends BaseMembre
{
    /**
     * Determine si le membre est le chef du groupe.
     */
    public function isChief(): bool
    {
        $chef = $this->getSecondaryGroup()->getPersonnage();

        return $chef === $this->getPersonnage();
    }
}
