<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\BackgroundRepository;

#[Entity(repositoryClass: BackgroundRepository::class)]
class Background extends BaseBackground
{
    public function __construct()
    {
        parent::__construct();
        $this->setUpdateDate(new \DateTime('NOW'));
        $this->setCreationDate(new \DateTime('NOW'));
    }
}
