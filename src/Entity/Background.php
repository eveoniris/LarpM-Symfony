<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BackgroundRepository;
use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: BackgroundRepository::class)]
class Background extends BaseBackground
{
    public function __construct()
    {
        parent::__construct();
        $this->setUpdateDate(new DateTime('NOW'));
        $this->setCreationDate(new DateTime('NOW'));
    }
}
