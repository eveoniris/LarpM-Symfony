<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RestrictionRepository;
use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: RestrictionRepository::class)]
class Restriction extends BaseRestriction implements Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    /**
     * Fourni le créateur de la restriction.
     */
    public function getAuteur(): User
    {
        return $this->getUserRelatedByAuteurId();
    }

    /**
     * Défini le créateur de la restriction.
     */
    public function setAuteur(User $User): static
    {
        $this->setUserRelatedByAuteurId($User);

        return $this;
    }
}
