<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompetenceAttributeRepository;
use ArrayAccess;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

/** @phpstan-ignore missingType.generics */
#[Entity(repositoryClass: CompetenceAttributeRepository::class)]
class CompetenceAttribute extends BaseCompetenceAttribute implements ArrayAccess, Stringable
{
    /**
     * Pour utilisation en tant que string.
     */
    public function __toString(): string
    {
        return 'CompetenceAttribute';
    }

    public function offsetGet($offset): mixed
    {
        return null;
    }

    public function offsetExists($offset): bool
    {
        return false;
    }

    public function offsetUnset($offset): void
    {
    }

    public function offsetSet($offset, $value): void
    {
    }
}
