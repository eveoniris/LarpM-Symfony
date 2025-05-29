<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Postulant extends BasePostulant
{
    public function __construct()
    {
        $this->setDate(new \DateTime());
    }
}
