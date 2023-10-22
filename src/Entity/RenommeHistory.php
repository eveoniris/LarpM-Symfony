<?php

namespace App\Entity;

use App\Entity\BaseRenommeHistory as parentAlias;

class RenommeHistory extends parentAlias
{
    public function __construct()
    {
        parent::__construct();
        $this->setDate(new \DateTime('NOW'));
    }
}
