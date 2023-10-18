<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AnnonceRepository;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce extends BaseAnnonce
{
    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
    }
}
