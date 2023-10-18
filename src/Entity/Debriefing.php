<?php

namespace App\Entity;

use App\Repository\DebriefingRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: DebriefingRepository::class)]
class Debriefing extends BaseDebriefing
{
     public function __construct()
    {
        parent::__construct();
        $this->setUpdateDate(new \DateTime('NOW'));
        $this->setCreationDate(new \DateTime('NOW'));
    }

    public function getPrintTitre(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getTitre()));
    }
}
