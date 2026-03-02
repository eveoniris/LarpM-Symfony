<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupeLangueRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeLangueRepository::class)]
class GroupeLangue extends BaseGroupeLangue
{
}
