<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LevelRepository::class)]
class Level extends BaseLevel
{
    public function getIndexLabel(): string
    {
        return $this->getIndex() . ' - ' . $this->getLabel();
    }
}
