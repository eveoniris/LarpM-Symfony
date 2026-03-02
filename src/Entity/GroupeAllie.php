<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupeAllieRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeAllieRepository::class)]
class GroupeAllie extends BaseGroupeAllie
{
    public function setGroupe(Groupe $groupe): static
    {
        return $this->setGroupeRelatedByGroupeId($groupe);
    }

    public function getGroupe(): Groupe
    {
        return $this->getGroupeRelatedByGroupeId();
    }

    public function setRequestedGroupe(Groupe $groupe): static
    {
        return $this->setGroupeRelatedByGroupeAllieId($groupe);
    }

    public function getRequestedGroupe(): Groupe
    {
        return $this->getGroupeRelatedByGroupeAllieId();
    }
}
