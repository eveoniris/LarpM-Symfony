<?php

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

    public function getCompetenceFamilyLabelsInCommons(): array
    {
        $competenceFamilyInCommons = [];
        $competenceFamilyInCommonsIntersect = array_intersect(
            $this->competenceFamilyFavorites->toArray(),
            $this->competenceFamilyNormales->toArray()
        );

        foreach ($competenceFamilyInCommonsIntersect as $competenceFamilyInCommon) {
            $competenceFamilyInCommons[] = $competenceFamilyInCommon->getLabel();
        }

        return $competenceFamilyInCommons;
    }

    public function isFavoriteCompetenceFamily(string|CompetenceFamily $competenceFamily): bool
    {
        foreach ($this->competenceFamilyFavorites as $competenceFamilyFavorite) {
            if (
                (is_string($competenceFamily) && $competenceFamilyFavorite->getLabel() === $competenceFamily)
                || ($competenceFamily instanceof CompetenceFamily
                    && $competenceFamily->getId() === $competenceFamilyFavorite->getId())
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
                (is_string($competenceFamily) && $competenceFamilyNormale->getLabel() === $competenceFamily)
                || ($competenceFamily instanceof CompetenceFamily
                    && $competenceFamily->getId() === $competenceFamilyNormale->getId())
            ) {
                return true;
            }
        }

        return false;
    }

    public function getCompetenceFamilyCreationLabelsInNotInFavorites(): array
    {
        $competenceFamiliesLabels = [];
        $competenceFamiliesIntersect = array_diff(
            $this->competenceFamilyCreations->toArray(),
            $this->competenceFamilyFavorites->toArray()
        );

        foreach ($competenceFamiliesIntersect as $competenceFamilyIntersect) {
            $competenceFamiliesLabels[] = $competenceFamilyIntersect->getLabel();
        }

        return $competenceFamiliesLabels;
    }

    public function getLabel(): string
    {
        return $this->getLabelFeminin().' / '.$this->getLabelMasculin();
    }
}
