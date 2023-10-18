<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity()]
class Evenement extends BaseEvenement
{
    public function __construct()
    {
        $this->setDateCreation(new \DateTime('NOW'));
        $this->setDateUpdate(new \DateTime('NOW'));
        parent::__construct();
    }
}
