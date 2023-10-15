<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\AttributeTypeRepository')]
class AttributeType extends BaseAttributeType
{
    public static $LANGUE = 'Langues';
    public static $LANGUE_ANCIENNE = 'Langues anciennes';
    public static $POTIONS = ['Potion 1', 'Potion 2', 'Potion 3', 'Potion 4'];
    public static $SORTS = ['Sort 1', 'Sort 2', 'Sort 3', 'Sort 4'];
    public static $PRIERES = ['Prière 1', 'Prière 2', 'Prière 3', 'Prière 4'];

    public function __toString(): string
    {
        return $this->getLabel();
    }
}