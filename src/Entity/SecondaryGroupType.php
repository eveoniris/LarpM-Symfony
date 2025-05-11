<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class SecondaryGroupType extends BaseSecondaryGroupType
{
    public function isReligion(): bool
    {
        return 'religion' === strtolower($this->getLabel());
    }
}
