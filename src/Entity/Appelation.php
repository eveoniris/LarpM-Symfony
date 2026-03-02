<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AppelationRepository;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: AppelationRepository::class)]
class Appelation extends BaseAppelation implements Stringable
{
    public function __toString(): string
    {
        return $this->getLabelTree();
    }

    public function stepCount(int $count = 0): int
    {
        if ($this->getAppelation()) {
            return $this->getAppelation()->stepCount($count + 1);
        }

        return $count;
    }

    public function getLabelTree(): string
    {
        $string = $this->getLabel();

        if (0 !== $this->getAppelations()->count()) {
            $string .= ' > ';
            $string .= implode(', ', $this->getAppelations()->toArray());
        }

        return $string;
    }
}
