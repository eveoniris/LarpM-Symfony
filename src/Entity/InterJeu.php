<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InterJeuEtat;
use App\Enum\InterJeuParticipantStatut;
use App\Repository\InterJeuRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterJeuRepository::class)]
class InterJeu extends BaseInterJeu
{
    public function getParticipation(Personnage $personnage): ?InterJeuPersonnage
    {
        foreach ($this->participations as $participation) {
            if ($participation->getPersonnage() === $personnage) {
                return $participation;
            }
        }

        return null;
    }

    /**
     * Personnages inscrits uniquement : ce sont eux qui comptent (statistiques, impressions, chronologie).
     *
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->participations
            ->filter(static fn (InterJeuPersonnage $participation): bool => $participation->isInscrit())
            ->map(static fn (InterJeuPersonnage $participation): Personnage => $participation->getPersonnage());
    }

    public function countByStatut(InterJeuParticipantStatut $statut): int
    {
        return $this->participations
            ->filter(static fn (InterJeuPersonnage $participation): bool => $participation->getStatut() === $statut)
            ->count();
    }

    public function countPropositions(): int
    {
        return $this->participations
            ->filter(static fn (InterJeuPersonnage $participation): bool => $participation->hasProposition())
            ->count();
    }

    /**
     * Ajoute le personnage, ou met à jour son statut s'il est déjà dans la liste.
     */
    public function addPersonnage(Personnage $personnage, InterJeuParticipantStatut $statut = InterJeuParticipantStatut::INSCRIT): InterJeuPersonnage
    {
        $participation = $this->getParticipation($personnage);
        if (null !== $participation) {
            return $participation->setStatut($statut);
        }

        $participation = new InterJeuPersonnage($this, $personnage, $statut);
        $this->participations->add($participation);

        return $participation;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $participation = $this->getParticipation($personnage);
        if (null !== $participation) {
            $this->participations->removeElement($participation);
        }

        return $this;
    }

    public function getOrga(User $user): ?InterJeuOrga
    {
        foreach ($this->orgas as $orga) {
            if ($orga->getUser()->getId() === $user->getId()) {
                return $orga;
            }
        }

        return null;
    }

    public function addOrga(User $user, ?User $validePar = null): InterJeuOrga
    {
        $orga = $this->getOrga($user);
        if (null === $orga) {
            $orga = new InterJeuOrga($this, $user, $validePar);
            $this->orgas->add($orga);
        }

        return $orga;
    }

    public function removeOrga(InterJeuOrga $orga): static
    {
        $this->orgas->removeElement($orga);

        return $this;
    }

    public function canGenereteChronologie(): bool
    {
        return $this->isDateReelPassed() && $this->getEtat() === InterJeuEtat::TERMINE && !$this->isChronologieGeneree();
    }
}
