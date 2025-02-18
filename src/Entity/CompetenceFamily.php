<?php

namespace App\Entity;

use App\Enum\CompetenceFamilyType;
use App\Repository\CompetenceFamilyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: CompetenceFamilyRepository::class)]
class CompetenceFamily extends BaseCompetenceFamily implements \Stringable
{
    public static $LITTERATURE = 'Littérature';

    /**
     * @ManyToMany(targetEntity="Classe", mappedBy="competenceFamilyFavorites")
     */
    protected $classeFavorites;

    /**
     * @ManyToMany(targetEntity="Classe", mappedBy="competenceFamilyNormales")
     */
    protected $classeNormales;

    /**
     * @ManyToMany(targetEntity="Classe", mappedBy="competenceFamilyCreations")
     */
    protected $classeCreations;

    public function __construct()
    {
        $this->classeFavorites = new ArrayCollection();
        $this->classeNormales = new ArrayCollection();
        $this->classeCreations = new ArrayCollection();

        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->getLabel() ?? '';
    }

    /**
     * Surcharge pour gérer le cas ou le parent retourne une valeur null pour un String attendu.
     */
    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function getCompetenceFamilyType(): ?CompetenceFamilyType
    {
        return CompetenceFamilyType::getFromLabel($this->label);
    }

    /**
     * Surcharge pour gérer le cas ou le parent retourne une valeur null pour un String attendu.
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function isSecretCompetenceFamily(): bool
    {
        return $this->getId() < 0 || CompetenceFamilyType::SECRET->value === $this->getCompetenceFamilyType()?->value;
    }

    /**
     * Fourni la compétence de premier niveau d'une famille de compétence.
     *
     * @return Competence $competenceFirst
     */
    public function getFirstCompetence()
    {
        $minimumIndex = null;
        $competenceFirst = null;

        foreach ($this->getCompetences() as $competence) {
            if (null == $minimumIndex) {
                $competenceFirst = $competence;
                $minimumIndex = $competence->getLevel()->getIndex();
            } elseif ($competence->getLevel()->getIndex() < $minimumIndex) {
                $competenceFirst = $competence;
                $minimumIndex = $competence->getLevel()->getIndex();
            }
        }

        return $competenceFirst;
    }

    /**
     * Fourni la compétence de plus haut niveau d'une famille de compétence.
     */
    public function getLastCompetence()
    {
        $maximumIndex = null;
        $competenceLast = null;

        foreach ($this->getCompetences() as $competence) {
            if (null == $maximumIndex) {
                $competenceLast = $competence;
                $maximumIndex = $competence->getLevel()->getIndex();
            } elseif ($competence->getLevel()->getIndex() > $maximumIndex) {
                $competenceLast = $competence;
                $maximumIndex = $competence->getLevel()->getIndex();
            }
        }

        return $competenceLast;
    }

    /**
     * Add Classe entity to collection.
     *
     * @return CompetenceFamily
     */
    public function addClasseFavorite(Classe $classe): static
    {
        $this->classeFavorites[] = $classe;

        return $this;
    }

    /**
     * Remove Classe entity from collection.
     *
     * @return CompetenceFamily
     */
    public function removeClasseFavorite(Classe $classe): static
    {
        $this->classeFavorites->removeElement($classe);

        return $this;
    }

    /**
     * Get Objet entity collection.
     *
     * @return Collection
     */
    public function getClasseFavorites()
    {
        return $this->classeFavorites;
    }

    /**
     * Add Classe entity to collection.
     *
     * @return CompetenceFamily
     */
    public function addClasseNormale(Classe $classe): static
    {
        $this->classeNormales[] = $classe;

        return $this;
    }

    /**
     * Remove Classe entity from collection.
     *
     * @return CompetenceFamily
     */
    public function removeClasseNormale(Classe $classe): static
    {
        $this->classeNormales->removeElement($classe);

        return $this;
    }

    /**
     * Get Objet entity collection.
     *
     * @return Collection
     */
    public function getClasseNormales()
    {
        return $this->classeNormales;
    }

    /**
     * Add Classe entity to collection.
     *
     * @return CompetenceFamily
     */
    public function addClasseCreation(Classe $classe): static
    {
        $this->classeCreations[] = $classe;

        return $this;
    }

    /**
     * Remove Classe entity from collection.
     *
     * @return CompetenceFamily
     */
    public function removeClasseCreation(Classe $classe): static
    {
        $this->classeCreations->removeElement($classe);

        return $this;
    }

    /**
     * Get Objet entity collection.
     *
     * @return Collection
     */
    public function getClasseCreations()
    {
        return $this->classeCreations;
    }

    /**
     * Fourni la description débarassé de sa mise en forme.
     */
    public function getDescriptionRaw(): string
    {
        return strip_tags($this->getDescription());
    }
}
