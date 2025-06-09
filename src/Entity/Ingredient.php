<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: IngredientRepository::class)]
class Ingredient extends BaseIngredient implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function fullLabel(): string
    {
        return $this->getLabel().' - '.$this->getColor().' (Niveau '.$this->getNiveau().') : '.$this->getDose();
    }

    public function getColor(): string
    {
        $color = 'Blanc';

        return match ($this->getNiveau()) {
            1 => 'Jaune',
            2 => 'Bleu',
            3 => 'Rouge',
            4 => 'Gris',
            default => $color,
        };
    }

    public function getQuantite(): int
    {
        return 5;
    }
}
