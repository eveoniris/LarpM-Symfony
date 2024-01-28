<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Tag extends BaseTag implements \Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }
}
