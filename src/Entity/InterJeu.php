<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InterJeuEtat;
use App\Repository\InterJeuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterJeuRepository::class)]
class InterJeu extends BaseInterJeu
{
    public function canGenereteChronologie(): bool
    {
        return $this->isDateReelPassed() && $this->getEtat() === InterJeuEtat::TERMINE;
    }
}
