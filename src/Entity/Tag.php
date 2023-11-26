<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: AnnonceRepository::class)]
class Tag extends BaseTag implements \Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }
}
