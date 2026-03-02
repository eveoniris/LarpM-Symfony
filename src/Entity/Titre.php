<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TitreRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: TitreRepository::class)]
class Titre extends BaseTitre implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
