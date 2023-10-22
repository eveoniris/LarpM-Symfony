<?php

namespace App\Entity;

use App\Repository\SortRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: SortRepository::class)]
class Sort extends BaseSort
{
    public function getFullLabel(): string
    {
        return $this->getLabel().' - '.$this->getDomaine()->getLabel().' Niveau '.$this->getNiveau();
    }

    public function getPrintLabel(): string|array|null
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel().'_'.$this->getDomaine()->getLabel().'_'.$this->getNiveau()));
    }
}
