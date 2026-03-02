<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Postulant extends BasePostulant
{
    public function __construct()
    {
        $this->setDate(new DateTime());
    }
}
