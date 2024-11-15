<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Service\CompetenceService;

class MagieService extends CompetenceService
{
    protected bool $hasBonus = true;

    public function canGetBonus(): bool
    {
        // TODO by competence LEVEL
        // Todo Domaine magie ?
        $personnageNbSorts = $this->getPersonnage()->getSorts()->count();
        $availableSorts = $this->entityManager->getRepository(Sort::class)->count();

        // Plus de sorts à apprendre
        if ($personnageNbSorts >= $availableSorts) {
            return false;
        }

        return parent::canGetBonus();
    }

    public function give(): void
    {
        // TODO filter Domaine if Personnage know all domaine;
        // TODO Filter SORT tag if Personnage know all Sort of given Level
        $this->applyRules($this->getRules());
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());
    }


    public function getRules(): array
    {
        return [
            // le personnage doit choisir un domaine de magie et un sort de niveau 1
            Level::NIVEAU_1 => [
                PersonnageTrigger::TAG_DOMAINE_MAGIE => 1,
                PersonnageTrigger::TAG_SORT_APPRENTI => 1,
            ],
            // le personnage peut choisir un nouveau domaine de magie et un sort de niveau 2
            Level::NIVEAU_2 => [PersonnageTrigger::TAG_SORT_INITIE => 1],
            // il obtient aussi la possibilité de choisir un sort de niveau 3
            Level::NIVEAU_3 => [
                PersonnageTrigger::TAG_DOMAINE_MAGIE => 1,
                PersonnageTrigger::TAG_SORT_EXPERT => 1,
            ],
            //  il obtient aussi la possibilité de choisir un sort de niveau 4
            Level::NIVEAU_4 => [PersonnageTrigger::TAG_SORT_MAITRE => 1],
        ];
    }
}
