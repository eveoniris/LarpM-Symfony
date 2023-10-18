<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'App\Repository\ObjectRepository')]
class Objectif extends BaseObjectif
{
    public function __construct()
    {
        $this->setDateCreation(new \DateTime('NOW'));
        $this->setDateUpdate(new \DateTime('NOW'));
        parent::__construct();
    }
}
