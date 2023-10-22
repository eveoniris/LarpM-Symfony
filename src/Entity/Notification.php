<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Notification extends BaseNotification
{
    public function __construct()
    {
        parent::__construct();
        $this->setDate(new \DateTime('NOW'));
    }
}
