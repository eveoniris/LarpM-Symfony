<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\BackgroundRepository')]
class Background extends BaseBackground
{
    public function __construct()
    {
        $this->setUpdateDate(new \DateTime('NOW'));
        if (empty($this->creation_date)) {
            $this->setCreationDate(new \DateTime('NOW'));
        }
    }
}