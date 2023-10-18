<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\CompetenceAttributeRepository;

#[Entity(repositoryClass: CompetenceAttributeRepository::class)]
class CompetenceAttribute extends BaseCompetenceAttribute implements \ArrayAccess, \Stringable
{
    /**
     * Pour utilisation en tant que string.
     */
    public function __toString(): string
    {
        return 'CompetenceAttribute';
    }

    public function offsetGet($offset): void
    {
    }

    public function offsetExists($offset): void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function offsetSet($offset, $value): void
    {
    }
}
