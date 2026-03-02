<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Evenement extends BaseEvenement
{
    public function __construct()
    {
        $this->setDateCreation(new DateTime('NOW'));
        $this->setDateUpdate(new DateTime('NOW'));
        parent::__construct();
    }
}
