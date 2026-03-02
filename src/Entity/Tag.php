<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity]
class Tag extends BaseTag implements Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }
}
