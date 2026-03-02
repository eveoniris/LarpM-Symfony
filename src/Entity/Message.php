<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Message extends BaseMessage
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }
}
