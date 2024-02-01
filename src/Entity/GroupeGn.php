<?php

namespace App\Entity;

use App\Repository\GroupeGnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: GroupeGnRepository::class)]
class GroupeGn extends BaseGroupeGn
{
    public function __construct()
    {
        parent::__construct();
        $this->agents ??= 0;
        $this->sieges ??= 0;
        $this->initiative ??= 0;
        $this->bateaux ??= 0;
    }

    /**
     * Défini le responsable de cette session de jeu.
     */
    public function setResponsable(Participant $participant): static
    {
        $this->setParticipant($participant);

        return $this;
    }

    /**
     * Fourni le responsable de cette session de jeu.
     */
    public function getResponsable(): ?Participant
    {
        return $this->getParticipant();
    }

    /**
     * Supprime le responsable de cette session de jeu.
     */
    public function setResponsableNull(): static
    {
        return $this->setParticipant();
    }

    /**
     * Fourni la liste des personnages de cette session de jeu.
     */
    public function getPersonnages(): Collection
    {
        $personnages = new ArrayCollection();

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getPersonnage()) {
                $personnages[] = $participant->getPersonnage();
            }
        }

        return $personnages;
    }

    public function addAgent(): static
    {
        ++$this->agents;

        return $this;
    }

    public function addBateau(): static
    {
        ++$this->bateaux;

        return $this;
    }
}
