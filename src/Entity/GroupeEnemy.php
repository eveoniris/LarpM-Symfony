<?php

namespace App\Entity;

use App\Repository\GroupeEnemyRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeEnemyRepository::class)]
class GroupeEnemy extends BaseGroupeEnemy
{
    public function setGroupe(Groupe $groupe): static
    {
        return $this->setGroupeRelatedByGroupeId($groupe);
    }

    public function getGroupe(): static
    {
        return $this->getGroupeRelatedByGroupeId();
    }

    public function setRequestedGroupe(Groupe $groupe)
    {
        return $this->setGroupeRelatedByGroupeEnemyId($groupe);
    }

    public function getRequestedGroupe()
    {
        return $this->getGroupeRelatedByGroupeEnemyId();
    }
}
