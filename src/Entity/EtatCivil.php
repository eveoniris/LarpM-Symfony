<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class EtatCivil extends BaseEtatCivil implements \Stringable
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        return $this->getNom().' '.$this->getPrenom();
    }
}
