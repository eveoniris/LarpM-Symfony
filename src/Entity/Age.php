<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\AgeRepository')]
class Age extends BaseAge implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getFullLabel(): string
    {
        return $this->getLabel().' '.$this->getDescription();
    }
}