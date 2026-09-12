<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ClasseRepository::class)]
class Classe extends BaseClasse
{
    public function __toString(): string
    {
        return $this->getLabel();
    }

    /** @return array<int, string> */
    public function getCompetenceFamilyLabelsInCommons(): array
    {
        $competenceFamilyInCommons = [];
        $competenceFamilyInCommonsIntersect = array_intersect($this->competenceFamilyFavorites->toArray(), $this->competenceFamilyNormales->toArray());

        foreach ($competenceFamilyInCommonsIntersect as $competenceFamilyInCommon) {
            $competenceFamilyInCommons[] = $competenceFamilyInCommon->getLabel();
        }

        return $competenceFamilyInCommons;
    }

    public function isFavoriteCompetenceFamily(string|CompetenceFamily $competenceFamily): bool
    {
        foreach ($this->competenceFamilyFavorites as $competenceFamilyFavorite) {
            if (
                \is_string($competenceFamily) && $competenceFamilyFavorite->getLabel() === $competenceFamily
                || $competenceFamily instanceof CompetenceFamily && $competenceFamily->getId() === $competenceFamilyFavorite->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    public function isCommonCompetenceFamily(string|CompetenceFamily $competenceFamily): bool
    {
        foreach ($this->competenceFamilyNormales as $competenceFamilyNormale) {
            if (
                \is_string($competenceFamily) && $competenceFamilyNormale->getLabel() === $competenceFamily
                || $competenceFamily instanceof CompetenceFamily && $competenceFamily->getId() === $competenceFamilyNormale->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public function getCompetenceFamilyCreationLabelsInNotInFavorites(): array
    {
        $competenceFamiliesLabels = [];
        $competenceFamiliesIntersect = array_diff($this->competenceFamilyCreations->toArray(), $this->competenceFamilyFavorites->toArray());

        foreach ($competenceFamiliesIntersect as $competenceFamilyIntersect) {
            $competenceFamiliesLabels[] = $competenceFamilyIntersect->getLabel();
        }

        return $competenceFamiliesLabels;
    }

    /**
     * Libellé accordé au genre donné.
     *
     * Le masculin est la forme par défaut : c'est ce que fait déjà
     * Personnage::getClasseName(), on garde la même règle pour rester cohérent
     * partout où une classe est affichée.
     *
     * @todo évoluer vers un modèle où les libellés varient en fonction du genre
     */
    public function getLabelPourGenre(?Genre $genre = null): string
    {
        return null === $genre || 'Masculin' === $genre->getLabel()
            ? $this->getLabelMasculin()
            : $this->getLabelFeminin();
    }

    /**
     * Les deux formes, quand aucun personnage de référence ne permet d'accorder.
     */
    public function getLabel(): string
    {
        return $this->getLabelFeminin() . ' / ' . $this->getLabelMasculin();
    }
}
