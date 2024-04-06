<?php

namespace App\Entity;

use App\Repository\ConnaissanceRepository;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Entity(repositoryClass: ConnaissanceRepository::class)]
class Connaissance extends BaseConnaissance
{
    public function getFullLabel(): string
    {
        return $this->getLabel();
    }

    public function getPrintLabel(): ?string
    {
        return (new AsciiSlugger())->slug($this->getLabel());
    }
}
