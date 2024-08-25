<?php

namespace App\Entity;

use App\Repository\DomaineRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: DomaineRepository::class)]
class Domaine extends BaseDomaine
{
    public function getFullDescription(): string
    {
        return $this->getLabel().' - '.$this->getDescription();
    }
}
