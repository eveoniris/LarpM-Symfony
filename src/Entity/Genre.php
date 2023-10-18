<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Genre extends BaseGenre implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
