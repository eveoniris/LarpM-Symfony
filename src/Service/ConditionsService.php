<?php

namespace App\Service;

use App\Entity\Bonus;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Groupe;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;

class ConditionsService
{
    public function isValidConditions(
        Groupe|Personnage|CompetenceFamily $entity,
        array $conditions,
        Bonus $bonus,
    ): bool {
        if (empty($conditions)) {
            return true;
        }

        // Tout en liste
        if (isset($conditions['type'])) {
            $conditions = [$conditions];
        }

        $mode = 'AND';
        foreach ($conditions as $condition) {
            // Par défaut les conditions sont des AND
            if ('OR' === $condition) {
                $mode = 'OR';
                continue;
            }

            if ($this->isValidCondition($entity, $condition, $bonus)) {
                // First OR mean TRUE
                if ('OR' === $mode) {
                    return true;
                }
                // In AND mode it's mean we only have a valid one, and we need to test others
            } elseif ('AND' === $mode) {
                // Any false condition in AND mode mean FALSE
                return false;
            }
        }

        return true;
    }

    protected function isValidCondition(
        Groupe|Personnage|CompetenceFamily $entity,
        array $condition,
        Bonus $bonus,
    ): bool {
        // condition non testable
        if (!$condition['type'] || !$condition['value']) {
            return true;
        }

        // Le personnage doit avoir cette origine pour que le bonus soit actif
        if (
            $entity instanceof Personnage
            && 'ORIGINE' === strtoupper($condition['type'])
            && $entity->getOrigine()?->getId() === (int)$condition['value']
        ) {
            return true;
        }

        // Parmi les langues "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'LANGUE' === strtoupper($condition['type'])) {
            $hasRequired = false;
            /** @var PersonnageLangues $languePersonnage */
            foreach ($entity->getPersonnageLangues() as $languePersonnage) {
                if ($languePersonnage->getLangue()?->getId() === (int)$condition['value']) {
                    // Do not return yet : if personnage had already the bonus langue
                    $hasRequired = true;
                }
            }
            if ($hasRequired) {
                return true;
            }
        }

        // Parmi les competences du personnage
        if ($entity instanceof Personnage && 'COMPETENCE' === strtoupper($condition['type'])) {
            if (is_numeric($condition['value'])) {
                return $entity->hasCompetenceId((int)$condition['value']);
            }

            return $entity->hasCompetenceLevel(
                CompetenceFamilyType::tryFrom($condition['value']),
                LevelType::tryFrom($condition['level']),
            );
        }

        // Parmi les familles de competence du personnage
        if ($entity instanceof Personnage && 'COMPETENCE_FAMILLE' === strtoupper($condition['type'])) {
            return $entity->hasCompetenceFamiliyId($condition['value']);
        }

        // Parmi les familles de competence de la compétence
        if ($entity instanceof CompetenceFamily && 'COMPETENCE_FAMILLE' === strtoupper($condition['type'])) {
            // dd($entity, $condition);

            return $entity->getId() === $condition['value'];
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'RELIGION' === strtoupper($condition['type'])) {
            return $entity->hasReligionId(
                (int)$condition['value'],
                $condition['level'] ?? 0,
            );
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'CLASSE' === strtoupper($condition['type'])) {
            return $entity->getClasse()->getId() === $condition['value'];
        }

        // TODO : Unique et ID déjà présent dans personnage_bonus > false

        // other type ?

        return false;
    }
}
