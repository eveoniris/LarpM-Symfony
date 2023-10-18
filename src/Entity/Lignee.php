<?php

namespace App\Entity;

use App\Repository\LigneesRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LigneesRepository::class)]
class Lignee extends BaseLignee implements \Stringable
{
    public function __toString(): string
    {
        return $this->getNom();
    }
}
