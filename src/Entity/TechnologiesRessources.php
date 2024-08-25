<?php

namespace App\Entity;

use App\Repository\TechnologiesRessourcesRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: TechnologiesRessourcesRepository::class)]
class TechnologiesRessources extends BaseTechnologiesRessources
{
}
