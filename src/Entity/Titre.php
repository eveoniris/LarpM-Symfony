<?php

namespace App\Entity;

use App\Repository\TitreRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TitreRepository::class)]
class Titre extends BaseTitre implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
