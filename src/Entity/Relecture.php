<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Relecture extends BaseRelecture
{
    public function __construct()
    {
        $this->setDate(new DateTime('NOW'));
        parent::__construct();
    }
}
