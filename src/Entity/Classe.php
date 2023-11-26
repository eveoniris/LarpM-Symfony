<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OrderBy;
use App\Repository\ClasseRepository;

#[Entity(repositoryClass: ClasseRepository::class)]
class Classe extends BaseClasse
{
    #[ManyToOne(targetEntity: CompetenceFamily::class, inversedBy: 'classeFavorites')]
    #[JoinTable(
        name: 'classe_competence_family_favorite',
        joinColumns: ['name' => 'class_id', 'referencedColumnName' => 'id'],
        inverseJoinColumns: ['JoinColumn' => ['name' => 'competence_family_id', 'referencedColumnName' => 'id']]
    )]
    #[OrderBy(['label' => \Doctrine\Common\Collections\Criteria::ASC])]
    protected Collection $competenceFamilyFavorites;

    #[ManyToMany(targetEntity: CompetenceFamily::class, inversedBy: 'classeNormales')]
    #[JoinTable(name: 'classe_competence_family_normale')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => \Doctrine\Common\Collections\Criteria::ASC])]
    protected Collection $competenceFamilyNormales;

    #[ManyToMany(targetEntity: CompetenceFamily::class, inversedBy: 'classeCreations')]
    #[JoinTable(name: 'classe_competence_family_creation')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'competence_family_id', referencedColumnName: 'id')]
    #[OrderBy(['label' => \Doctrine\Common\Collections\Criteria::ASC])]
    protected Collection $competenceFamilyCreations;

    public function __construct()
    {
        $this->competenceFamilyFavorites = new ArrayCollection();
        $this->competenceFamilyNormales = new ArrayCollection();
        $this->competenceFamilyCreations = new ArrayCollection();
        parent::__construct();
    }

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

    public function addCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->addClasseFavorite($this);
        $this->competenceFamilyFavorites[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyFavorite(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->removeClasseFavorite($this);
        $this->competenceFamilyFavorites->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyFavorites(): Collection
    {
        return $this->competenceFamilyFavorites;
    }

    public function addCompetenceFamilyNormale(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->addClasseNormale($this);
        $this->competenceFamilyNormales[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyNormale(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->removeClasseNormale($this);
        $this->competenceFamilyNormales->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyNormales(): Collection
    {
        return $this->competenceFamilyNormales;
    }

    public function addCompetenceFamilyCreation(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->addClasseCreation($this);
        $this->competenceFamilyCreations[] = $competenceFamily;

        return $this;
    }

    public function removeCompetenceFamilyCreation(CompetenceFamily $competenceFamily): self
    {
        $competenceFamily->removeClasseCreation($this);
        $this->competenceFamilyCreations->removeElement($competenceFamily);

        return $this;
    }

    public function getCompetenceFamilyCreations(): Collection
    {
        return $this->competenceFamilyCreations;
    }
}
