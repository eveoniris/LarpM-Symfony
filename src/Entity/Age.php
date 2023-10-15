<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

/**
 * App\Entity\Age
 *
 * @Entity(repositoryClass="LarpManager\Repository\AgeRepository")
 */
#[Entity(repositoryClass: 'LarpManager\Repository\AgeRepository')]
class Age extends BaseAge
{

    public function __toString()
    {
        return $this->getLabel();
    }

    public function getFullLabel()
    {
        return $this->getLabel() . ' ' . $this->getDescription();
    }
}