<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\ObjectRepository;

#[Entity(repositoryClass: ObjectRepository::class)]
class Objectif extends BaseObjectif
{
    public function __construct()
    {
        $this->setDateCreation(new \DateTime('NOW'));
        $this->setDateUpdate(new \DateTime('NOW'));
        parent::__construct();
    }
}
