<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\ObjectRepository;

#[Entity(repositoryClass: ObjectRepository::class)]
class Objectif extends BaseObjectif
{
    public function __construct()
    {
        $this->setDateCreation(new DateTime('NOW'));
        $this->setDateUpdate(new DateTime('NOW'));
        parent::__construct();
    }
}
