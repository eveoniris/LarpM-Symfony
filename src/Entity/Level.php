<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LevelRepository::class)]
class Level extends BaseLevel
{
    public const NIVEAU_1 = 1;
    public const NIVEAU_2 = 2;
    public const NIVEAU_3 = 3;
    public const NIVEAU_4 = 4;
    public const NIVEAU_5 = 5;

    public function getIndexLabel(): string
    {
        return $this->getIndex().' - '.$this->getLabel();
    }

    public function __toString(): string
    {
        return $this->getIndexLabel();
    }
}
