<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnnonceRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce extends BaseAnnonce
{
    public function __construct()
    {
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }
}
