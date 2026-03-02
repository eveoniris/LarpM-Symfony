<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PugilatHistory extends BasePugilatHistory
{
    public function __construct()
    {
        parent::__construct();
        $this->setDate(new DateTime('NOW'));
    }
}
