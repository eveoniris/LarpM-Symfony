<?php

namespace App\Entity;

use App\Repository\GroupeGnOrdreRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeGnOrdreRepository::class)]
class GroupeGnOrdre extends BaseGroupeGnOrdre
{

}
