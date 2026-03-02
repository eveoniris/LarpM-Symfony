<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: RessourceRepository::class)]
class Ressource extends BaseRessource implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getQuantite(): int
    {
        return 3;
    }
}
