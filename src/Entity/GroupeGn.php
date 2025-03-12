<?php

namespace App\Entity;

use App\Repository\GroupeGnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function hasTitle(User|UserInterface $user): bool
    {
        return $this->getSuzerin()?->getId() === $user->getId()
            || $this->getConnetable()?->getId() === $user->getId()
            || $this->getIntendant()?->getId() === $user->getId()
            || $this->getCamarilla()?->getId() === $user->getId()
            || $this->getNavigateur()?->getId() === $user->getId();
    }

    public function addAgent(): static
    {
        ++$this->agents;

        return $this;
    }

    public function isSuzerin(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            $personnage = $personnage->getPersonnage();
        }

        return $this->getSuzerin()?->getId() === $personnage->getId();
    }

    public function isConnetable(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            $personnage = $personnage->getPersonnage();
        }

        return $this->getConnetable()?->getId() === $personnage->getId();
    }

    public function isCamarilla(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            $personnage = $personnage->getPersonnage();
        }

        return $this->getCamarilla()?->getId() === $personnage->getId();
    }

    public function isIntendant(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            $personnage = $personnage->getPersonnage();
        }

        return $this->getIntendant()?->getId() === $personnage->getId();
    }

    public function isNavigateur(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            $personnage = $personnage->getPersonnage();
        }

        return $this->getNavigateur()?->getId() === $personnage->getId();
    }

    public function isResponsable(Personnage|Participant $personnage): bool
    {
        if ($personnage instanceof Participant) {
            return $this->getParticipant()?->getId() === $personnage->getId();
        }

        return $this->getParticipant()?->getPersonnage()?->getId() === $personnage->getId();
    }

    public function addBateau(): static
    {
        ++$this->bateaux;

        return $this;
    }
}
