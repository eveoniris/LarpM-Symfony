<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConstructionRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: ConstructionRepository::class)]
class Construction extends BaseConstruction implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getFullLabel(): string
    {
        return $this->getLabel() . ' ( protection : ' . $this->getDefense() . ' )';
    }
}
