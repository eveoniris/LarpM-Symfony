<?php

namespace App\Entity;

use App\Repository\SphereRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: SphereRepository::class)]
class Sphere extends BaseSphere
{
}
