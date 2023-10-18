<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Notification extends BaseNotification
{
    public function __contruct(): void
    {
        parent::__contruct();
        $this->setDate(new \DateTime('NOW'));
    }
}
