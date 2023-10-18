<?php

namespace App\Entity;

use App\Repository\IntrigueRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: IntrigueRepository::class)]
class Intrigue extends BaseIntrigue
{
    public function __construct()
    {
        $this->setDateCreation(new \DateTime('NOW'));
        $this->setDateUpdate(new \DateTime('NOW'));
        parent::__construct();
    }
}
