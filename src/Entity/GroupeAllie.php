<?php

namespace App\Entity;

use App\Repository\GroupeAllieRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeAllieRepository::class)]
class GroupeAllie extends BaseGroupeAllie
{
    public function setGroupe(Groupe $groupe)
    {
        return $this->setGroupeRelatedByGroupeId($groupe);
    }

    public function getGroupe()
    {
        return $this->getGroupeRelatedByGroupeId();
    }

    public function setRequestedGroupe(Groupe $groupe)
    {
        return $this->setGroupeRelatedByGroupeAllieId($groupe);
    }

    public function getRequestedGroupe()
    {
        return $this->getGroupeRelatedByGroupeAllieId();
    }
}
