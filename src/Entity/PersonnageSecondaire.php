<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageSecondaire extends BasePersonnageSecondaire
{
    /**
     * Fourni la liste des compétences.
     *
     * @return mixed[]
     */
    public function getCompetences(): array
    {
        $competences = [];
        $personnageSecondaireCompetences = $this->getPersonnageSecondaireCompetences();
        foreach ($personnageSecondaireCompetences as $personnageSecondaireCompetence) {
            $competences[] = $personnageSecondaireCompetence->getCompetence();
        }

        return $competences;
    }

    /**
     * Fourni le label de la classe en guise de label pour l'archétype.
     *
     * Les deux formes, faute de personnage de référence permettant d'accorder.
     */
    public function getLabel(): string
    {
        return $this->getClasse()?->getLabel() ?? '';
    }

    /**
     * Label de l'archétype accordé au genre donné.
     *
     * Un archétype n'a pas de genre propre : c'est celui du personnage qui
     * l'endosserait qui fait foi.
     */
    public function getLabelPourGenre(?Genre $genre = null): string
    {
        return $this->getClasse()?->getLabelPourGenre($genre) ?? '';
    }
}
