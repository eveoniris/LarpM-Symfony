<?php

namespace App\Entity;

use App\Repository\SecondaryGroupRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: SecondaryGroupRepository::class)]
class SecondaryGroup extends BaseSecondaryGroup implements \Stringable
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getActifs(Gn $gn): int
    {
        $count_actifs = 0;
        foreach ($this->getMembres() as $membre) {
            $count_actifs += (int) $membre->getPersonnage()->participeTo($gn);
        }

        return $count_actifs;
    }

    public function isMembreOrResponsable(Personnage $personnage): bool
    {
        return $this->isMembre($personnage) || $this->isResponsable($personnage);
    }

    /**
     * Vérifie si un personnage est membre du groupe.
     */
    public function isMembre(Personnage $personnage): bool
    {
        foreach ($this->getMembres() as $membre) {
            if ($membre->getPersonnage()->getId() === $personnage->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isResponsable(Personnage $personnage): bool
    {
        return $personnage->getId() === $this->getResponsable()?->getId();
    }

    /**
     * Fourni le personnage responsable du groupe.
     */
    public function getResponsable()
    {
        return $this->getPersonnage();
    }

    /**
     * Vérifie si un personnage est postulant à ce groupe.
     */
    public function isPostulant(Personnage $personnage): bool
    {
        foreach ($this->getPostulants() as $postulant) {
            if ($postulant->getPersonnage()->getId() === $personnage->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isReligion(): bool
    {
        return $this->secondaryGroupType->isReligion();
    }

    /**
     * Défini le personnage responsable du groupe.
     */
    public function setResponsable(Personnage $personnage): static
    {
        $this->setPersonnage($personnage);

        return $this;
    }
}
