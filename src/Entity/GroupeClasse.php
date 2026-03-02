<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeRepository::class)]
class GroupeClasse extends BaseGroupeClasse
{
}
