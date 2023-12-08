<?php

namespace App\Entity;

use App\Repository\LangueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LangueRepository::class)]
class Langue extends BaseLangue implements \Stringable
{

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getFullDescription(): string
    {
        return $this->getLabel().' : '.$this->getDescription();
    }


    /**
     * Fourni la liste des territoires ou la langue est la langue principale.
     */
    public function getTerritoirePrincipaux()
    {
        return $this->getTerritoires();
    }

    /**
     * Fourni la catégorie de la langue.
     */
    public function getCategorie(): string
    {
        $unknown = 'Inconnue';
        if (null === $this->getDiffusion()) {
            return $unknown;
        }

        return match ($this->getDiffusion()) {
            2 => 'Commune',
            1 => 'Courante',
            0 => 'Rare',
            default => $unknown,
        };
    }

    /**
     * Renvoie le libellé de diffusion, incluant la catégorie.
     */
    public function getDiffusionLabel(): string
    {
        return (null !== $this->getDiffusion() ? $this->getDiffusion().' - ' : '').$this->getCategorie();
    }

    public function getPrintLabel(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower($this->getLabel()));
    }
}
