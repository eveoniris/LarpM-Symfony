<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Domaine extends BaseDomaine
{
    public function getFullDescription(): string
    {
        return $this->getLabel().' - '.$this->getDescription();
    }
}
