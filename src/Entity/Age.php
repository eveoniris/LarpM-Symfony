<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\AgeRepository;

#[Entity(repositoryClass: AgeRepository::class)]
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
