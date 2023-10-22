<?php

namespace App\Entity;

use App\Repository\PotionRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: PotionRepository::class)]
class Potion extends BasePotion
{
    public function getFullLabel(): string
    {
        return $this->getNumero().' - '.$this->getLabel().' - Niveau '.$this->getNiveau();
    }

    public function getPrintLabel(): string|array|null
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getNumero().'_'.$this->getLabel().'_'.$this->getNiveau()));
    }
}
