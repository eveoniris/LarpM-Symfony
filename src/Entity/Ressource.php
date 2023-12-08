<?php

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RessourceRepository::class)]
class Ressource extends BaseRessource implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
