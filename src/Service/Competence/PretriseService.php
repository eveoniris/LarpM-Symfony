<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Entity\Priere;
use App\Enum\TriggerType;
use App\Service\CompetenceService;

class PretriseService extends CompetenceService
{
    protected bool $hasBonus = true;

    /**
     * Ajoute toutes les prières de niveau de sa compétence
     * liées aux sphères de sa religion fervente ou fanatique.
     */
    public function give(): void
    {
        // ajoute toutes les prières de niveau de sa compétence liées aux sphères de sa religion fervente ou fanatique
        $religion = $this->getPersonnage()->getMainReligion();

        if (!$religion) {
            return;
        }

        foreach ($religion->getSpheres() as $sphere) {
            $competenceIndex = $this->getCompetence()->getLevel()?->getIndex();
            /** @var Priere $priere */
            foreach ($sphere->getPrieres() as $priere) {
                if (
                    !$this->getPersonnage()->hasPriere($priere)
                    && $priere->getNiveau() === $competenceIndex
                ) {
                    // TODO test if priere is really added
                    $priere->addPersonnage($this->getPersonnage());
                    $this->getPersonnage()->addPriere($priere);
                }
            }
        }

        // TODO Filter by available

        $this->applyRules($this->getRules());
    }

    public function getRules(): array
    {
        return [
            // Initié : Vous connaissez le niveau fervent d'une Religion supplémentaire.
            // Expert : Vous connaissez le niveau Fervent de deux Religions supplémentaires.
            // Maitre : Vous connaissez le niveau Fervent de trois Religions supplémentaires.

            // Permet à un prêtre de choisir des infos de descriptions de religions qu'il ne connait pas
            Level::NIVEAU_2 => [TriggerType::PRETRISE_INITIE->value => 1],
            Level::NIVEAU_3 => [TriggerType::PRETRISE_INITIE->value => 2],
            Level::NIVEAU_4 => [TriggerType::PRETRISE_INITIE->value => 3],
        ];
    }

    public function remove(): void
    {
        // TODO remove priere ?

        $this->removeRules($this->getRules());
    }

    public function validateApprendre(): void
    {
        // le personnage doit avoir une religion au niveau fervent ou fanatique
        if (
            !$this->getPersonnage()->isCreation(
            ) // Todo voir pour forcer le choix de religion et niveau si la classe offre Prêtrise
            && !$this->getPersonnage()->isFervent() && !$this->getPersonnage()->isFanatique()
        ) {
            $this->addError(
                'Pour obtenir la compétence Prêtrise, vous devez être FERVENT ou FANATIQUE',
                self::ERR_CODE_LEARN,
            );
        }
    }
}
