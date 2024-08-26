<?php

namespace App\Entity;

use App\Repository\TechnologiesRessourcesRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TechnologiesRessourcesRepository::class)]
class TechnologiesRessources extends BaseTechnologiesRessources
{
    public function getLabel(): ?string
    {
        return $this->getQuantite().' '.$this->getRessource()->getLabel();
    }

    public function getDescription(): ?string
    {
        return sprintf('Pour la technologie %s', $this->getTechnologie()->getLabel());
    }
}
