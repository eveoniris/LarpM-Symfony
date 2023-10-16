<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\BackgroundRepository')]
class Background extends BaseBackground
{
    public function __construct()
    {
        parent::__construct();
        $this->setUpdateDate(new \DateTime('NOW'));
        if (!$this->creation_date instanceof \DateTime) {
            $this->setCreationDate(new \DateTime('NOW'));
        }
    }
}