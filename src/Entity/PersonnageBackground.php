<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageBackground extends BasePersonnageBackground
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }
}
