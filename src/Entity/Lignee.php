<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LigneeRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: LigneeRepository::class)]
class Lignee extends BaseLignee implements Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }

    public function getLabel(): string
    {
        return $this->getNom();
    }
}
