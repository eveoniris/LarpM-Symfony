<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupeGnDemandeRepository;
use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeGnDemandeRepository::class)]
class GroupeGnDemande extends BaseGroupeGnDemande
{
    public function __construct()
    {
        $this->setDate(new DateTime());
    }
}
