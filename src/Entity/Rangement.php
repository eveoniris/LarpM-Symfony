<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Rangement extends BaseRangement
{
    public function getAdresse(): string
    {
        $adresse = $this->getLabel();
        if ($this->getlocalisation()) {
            $adresse .= ' ('.$this->getLocalisation()->getLabel().')';
        }

        return $adresse;
    }
}
