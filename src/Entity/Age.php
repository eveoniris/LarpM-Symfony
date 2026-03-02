<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AgeRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: AgeRepository::class)]
class Age extends BaseAge implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getFullLabel(): string
    {
        return $this->getLabel() . ' ' . $this->getDescription();
    }
}
