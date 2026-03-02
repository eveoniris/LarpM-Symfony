<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReligionLevelRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ReligionLevelRepository::class)]
class ReligionLevel extends BaseReligionLevel
{
}
