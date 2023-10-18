<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class IntrigueHasModification extends BaseIntrigueHasModification
{
    public function __construct()
    {
        $this->setDate(new \DateTime('NOW'));
        parent::__construct();
    }
}
