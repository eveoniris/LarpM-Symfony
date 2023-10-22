<?php

namespace App\Entity;

use App\Repository\ConnaissanceRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ConnaissanceRepository::class)]
class Connaissance extends BaseConnaissance
{
    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }
}
