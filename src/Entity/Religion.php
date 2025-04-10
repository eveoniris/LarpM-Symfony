<?php

namespace App\Entity;

use App\Repository\ReligionRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ReligionRepository::class)]
class Religion extends BaseReligion implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function isSans(): bool
    {
        return 'sans' === strtolower($this->getLabel());
    }

    /**
     * Fourni la liste des territoires ou la religion est la religion principale.
     */
    public function getTerritoirePrincipaux()
    {
        return $this->getTerritoires();
    }

    public function getBlasonFileUrl()
    {
        // TODO params ?
        return 'img/religions/'.$this->getBlason();
    }
}
