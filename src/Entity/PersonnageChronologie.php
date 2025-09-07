<?php

namespace App\Entity;

use App\Enum\ChronologyType;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageChronologie extends BasePersonnageChronologie
{
    public function isFruitsEtLegumes(): bool
    {
        return $this->evenement === ChronologyType::FRUITS_AND_VEGETABLES->value;
    }
}
