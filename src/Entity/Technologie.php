<?php

namespace App\Entity;

use App\Repository\TechnologieRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TechnologieRepository::class)]
class Technologie extends BaseTechnologie
{
    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }
}
