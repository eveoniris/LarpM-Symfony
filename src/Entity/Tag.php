<?php

namespace App\Entity;
class Tag extends BaseTag implements \Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }
}
