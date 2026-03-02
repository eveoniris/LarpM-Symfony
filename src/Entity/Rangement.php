<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Rangement extends BaseRangement
{
    public function getAdresse(): string
    {
        $adresse = $this->getLabel();
        $adresse .= ' (' . $this->getLocalisation()->getLabel() . ')';

        return $adresse;
    }
}
