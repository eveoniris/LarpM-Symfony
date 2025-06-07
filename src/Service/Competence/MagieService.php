<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Entity\Sort;
use App\Enum\TriggerType;
use App\Service\CompetenceService;

class MagieService extends CompetenceService
{
    protected bool $hasBonus = true;

    public function canGetBonus(): bool
    {
        // TODO by competence LEVEL
        // Todo Domaine magie ?
        $personnageNbSorts = $this->getPersonnage()->getSorts()->count();
        $availableSorts = $this->entityManager->getRepository(Sort::class)->count([]);

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

    public function getRules(): array
    {
        return [
            // le personnage doit choisir un domaine de magie et un sort de niveau 1
            Level::NIVEAU_1 => [
                TriggerType::DOMAINE_MAGIE->value => 1,
                TriggerType::SORT_APPRENTI->value => 1,
            ],
            // le personnage peut choisir un nouveau domaine de magie et un sort de niveau 2
            Level::NIVEAU_2 => [TriggerType::SORT_INITIE->value => 1],
            // il obtient aussi la possibilité de choisir un sort de niveau 3
            Level::NIVEAU_3 => [
                TriggerType::MAGIE_EXPERT->value => 1,
                TriggerType::SORT_EXPERT->value => 1,
            ],
            //  il obtient aussi la possibilité de choisir un sort de niveau 4
            Level::NIVEAU_4 => [TriggerType::SORT_MAITRE->value => 1],
        ];
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());
    }
}
