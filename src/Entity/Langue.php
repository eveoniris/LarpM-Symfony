<?php

namespace App\Entity;

use App\Repository\LangueRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: LangueRepository::class)]
class Langue extends BaseLangue implements \Stringable
{
    public const DIFFUSION_COURANTE = 1;
    public const DIFFUSION_COMMUNE = 2;
    public const DIFFUSION_RARE = 0;
    public const DIFFUSION_INCONNUE = null;

    public const SECRET_VISIBLE = 0;
    public const SECRET_HIDDEN = 1;

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
        return match ($this->getDiffusion()) {
            self::DIFFUSION_COMMUNE => 'Commune',
            self::DIFFUSION_COURANTE => 'Courante',
            self::DIFFUSION_RARE => 'Rare',
            default => 'Inconnue',
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
