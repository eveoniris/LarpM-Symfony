<?php

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
     * Fourni le label de la classe en guide de la label pour l'archétype.
     */
    public function getLabel(): string
    {
        return $this->getClasse()->getLabel();
    }
}
