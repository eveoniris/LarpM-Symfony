<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity]
class Genre extends BaseGenre implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
