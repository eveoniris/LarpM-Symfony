<?php

namespace App\Entity;

class Relecture extends BaseRelecture
{
    public function __construct()
    {
        $this->setDate(new \DateTime('NOW'));
        parent::__construct();
    }
}
