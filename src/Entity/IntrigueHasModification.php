<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class IntrigueHasModification extends BaseIntrigueHasModification
{
    public function __construct()
    {
        $this->setDate(new DateTime('NOW'));
        parent::__construct();
    }
}
