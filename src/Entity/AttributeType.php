<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\AttributeTypeRepository;

#[Entity(repositoryClass: AttributeTypeRepository::class)]
class AttributeType extends BaseAttributeType implements \Stringable
{
    public static string $LANGUE = 'Langues';

    public static string $LANGUE_ANCIENNE = 'Langues anciennes';

    public static array $POTIONS = ['Potion 1', 'Potion 2', 'Potion 3', 'Potion 4'];

    public static array $SORTS = ['Sort 1', 'Sort 2', 'Sort 3', 'Sort 4'];

    public static array $PRIERES = ['Prière 1', 'Prière 2', 'Prière 3', 'Prière 4'];

    public function __toString(): string
    {
        return $this->getLabel();
    }
}
