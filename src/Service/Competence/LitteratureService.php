<?php

declare(strict_types=1);

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Enum\TriggerType;
use DateTime;
use App\Service\CompetenceService;

class LitteratureService extends CompetenceService
{
    /**
     * Index du niveau de Littérature => tags du sort et de la potion donnés (à l'achat, puis par GN joué).
     *
     * @var array<int, array{TriggerType, TriggerType}>
     */
    public const array CHOICE_TAGS = [
        Level::NIVEAU_2 => [TriggerType::SORT_APPRENTI, TriggerType::ALCHIMIE_APPRENTI],
        Level::NIVEAU_3 => [TriggerType::SORT_INITIE, TriggerType::ALCHIMIE_INITIE],
        Level::NIVEAU_4 => [TriggerType::SORT_EXPERT, TriggerType::ALCHIMIE_EXPERT],
        Level::NIVEAU_5 => [TriggerType::SORT_MAITRE, TriggerType::ALCHIMIE_MAITRE],
    ];

    protected bool $hasBonus = true;

    public function give(): void
    {
        // Todo filter if personnage know all of them or will reach more than exists
        $this->applyRules($this->getRules());

        // Sort et potion du niveau acheté, absorbés ensuite par le premier GN joué (lockGnGiveLiteratureGnBonus)
        foreach (self::CHOICE_TAGS[$this->getCompetence()->getLevel()?->getIndex()] ?? [] as $tag) {
            $trigger = new PersonnageTrigger();
            $trigger->setPersonnage($this->getPersonnage());
            $trigger->setTag($tag);
            $trigger->setDone(false);
            $trigger->setSource(PersonnageTrigger::SOURCE_ACHAT);
            $trigger->setCreatedAt(new DateTime());
            $this->entityManager->persist($trigger);
        }
    }

    /** @return array<int, array<string, int>> */
    public function getRules(): array
    {
        return [
            // 2 langues communes supplémentaires de son choix
            Level::NIVEAU_1 => [TriggerType::LANGUE_COURANTE->value => 2],
            Level::NIVEAU_2 => [
                //  Sait parler, lire et écrire trois autres langues vivantes (courante ou commune) de son choix.
                TriggerType::LANGUE_COURANTE->value => 3,
                // il obtient aussi la possibilité de choisir un sort de niveau 1
                // done on lock gn : TriggerType::SORT_APPRENTI->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 1
                // done on lock gn : TriggerType::ALCHIMIE_APPRENTI->value => 1,
            ],
            Level::NIVEAU_3 => [
                // Sait parler, lire et écrire un langage ancien ainsi que trois autres langues vivantes
                // (courante ou commune) de son choix ainsi qu'une langue ancienne
                TriggerType::LANGUE_COURANTE->value => 3,
                TriggerType::LANGUE_ANCIENNE->value => 1,
                // il obtient aussi la possibilité de choisir un sort et une potion de niveau 2
                // done on lock gn : TriggerType::SORT_INITIE->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 2
                // done on lock gn : TriggerType::ALCHIMIE_INITIE->value => 1,
            ],
            Level::NIVEAU_4 => [
                // Sait parler, lire et écrire un langage ancien ainsi que trois autres langues vivantes
                // (courante ou commune) de son choix ainsi qu'une langue ancienne
                TriggerType::LANGUE_COURANTE->value => 3,
                TriggerType::LANGUE_ANCIENNE->value => 1,
                // il obtient aussi la possibilité de choisir un sort et une potion de niveau 2
                // done on lock gn : TriggerType::SORT_EXPERT->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 2
                // done on lock gn : TriggerType::ALCHIMIE_EXPERT->value => 1,
            ],
            // todo GM all common langues + 1 old
        ];
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());

        // Retire le sort et la potion d'achat restés intacts (ni consommés ni absorbés par un GN)
        $tags = self::CHOICE_TAGS[$this->getCompetence()->getLevel()?->getIndex()] ?? [];
        foreach ($this->getPersonnage()->getPersonnageTriggers() as $trigger) {
            if ($trigger->isAchatNonRattache() && \in_array($trigger->getTag(), $tags, true)) {
                $trigger->consume('Compétence retirée')->setSource(null);
            }
        }
    }
}
