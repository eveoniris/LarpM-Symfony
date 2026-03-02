<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Notification extends BaseNotification
{
    public function __construct()
    {
        $this->setDate(new DateTime('NOW'));
    }
}
