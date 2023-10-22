<?php

namespace App\Entity;

use App\Repository\PriereRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: PriereRepository::class)]
class Priere extends BasePriere
{
    public function getFullLabel(): string
    {
        return $this->getSphere()->getLabel().' - '.$this->getNiveau().' - '.$this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $this->getFullLabel()));
    }
}
