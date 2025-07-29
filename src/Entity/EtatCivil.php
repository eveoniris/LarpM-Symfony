<?php

namespace App\Entity;

use App\Repository\EtatCivilRepository;
use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Stringable;

#[Entity(repositoryClass: EtatCivilRepository::class)]
class EtatCivil extends BaseEtatCivil implements Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        return $this->getNom() . ' ' . $this->getPrenom();
    }
}
