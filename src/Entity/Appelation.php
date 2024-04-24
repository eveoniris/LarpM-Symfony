<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\AppelationRepository;

#[Entity(repositoryClass: AppelationRepository::class)]
class Appelation extends BaseAppelation implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabelTree();
    }

    public function stepCount(int $count = 0): int
    {
        dump("ux");
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
