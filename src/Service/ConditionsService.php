<?php

namespace App\Service;

use App\Entity\Bonus;
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
        mixed $service = null,
    ): bool {
        if (empty($conditions)) {
            return true;
        }

        // Tout en liste
        if (isset($conditions['type'])) {
            $conditions = [$conditions];
        }

        $mode = 'AND';
        foreach ($conditions as $key => $condition) {
            // Par défaut les conditions sont des AND
            if ('OR' === $condition) {
                $mode = 'OR';
                continue;
            }

            // for some short condition data
            if (!is_array($condition)) {
                $condition = ['type' => $key, 'value' => $condition];
            }

            if ($this->isValidCondition($entity, $condition, $bonus, $service)) {
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
        mixed $service = null,
    ): bool {
        // condition non testable
        if (!$condition['type'] || !$condition['value']) {
            return true;
        }

        // Le personnage doit avoir cette origine pour que le bonus soit actif
        if (
            $entity instanceof Personnage
            && 'ORIGINE' === strtoupper($condition['type'])
            && $entity->getOrigine()?->getId() === (int) $condition['value']
        ) {
            return true;
        }

        // Parmi les langues "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'LANGUE' === strtoupper($condition['type'])) {
            $hasRequired = false;
            /** @var PersonnageLangues $languePersonnage */
            foreach ($entity->getPersonnageLangues() as $languePersonnage) {
                if ($languePersonnage->getLangue()?->getId() === (int) $condition['value']) {
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
                return $entity->hasCompetenceId((int) $condition['value']);
            }

            return $entity->hasCompetenceLevel(
                CompetenceFamilyType::tryFrom($condition['value']),
                LevelType::tryFrom($condition['level']),
            );
        }
        // Parmi les familles de competence du personnage
        if ($entity instanceof Personnage && 'COMPETENCE_FAMILLE' === strtoupper($condition['type'])) {
            if ($service instanceof CompetenceService) {
                if ($entity->getId() === $condition['value']) {
                    return true;
                }

                if (is_numeric($condition['value']) && $service->getCompetence()
                        ->getCompetenceFamily()
                        ?->getId() === (int) $condition['value']) {
                    return true;
                }

                if (strtoupper(
                        $service->getCompetence()
                            ->getCompetenceFamily()
                            ?->getCompetenceFamilyType()?->value,
                    ) === strtoupper($condition['value'])) {
                    return true;
                }
            }
        }

        // Parmi les familles de competence de la compétence
        if ($entity instanceof CompetenceFamily && 'COMPETENCE_FAMILLE' === strtoupper($condition['type'])) {
            if ($entity->getId() === $condition['value']) {
                return true;
            }

            if (
                strtoupper($entity->getCompetenceFamilyType()?->value) === strtoupper($condition['value'])
            ) {
                return true;
            }

            if ($service instanceof CompetenceService) {
                if (is_numeric($condition['value']) && $service->getCompetence()
                        ->getCompetenceFamily()
                        ?->getId() === (int) $condition['value']) {
                    return true;
                }

                if (strtoupper(
                        $service->getCompetence()
                            ->getCompetenceFamily()
                            ?->getCompetenceFamilyType()?->value,
                    ) === strtoupper($condition['value'])) {
                    return true;
                }
            }
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'RELIGION' === strtoupper($condition['type'])) {
            return $entity->hasReligionId(
                (int) $condition['value'],
                $condition['level'] ?? 0,
            );
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'CLASSE' === strtoupper($condition['type'])) {
            return $entity->getClasse()->getId() === $condition['value'];
        }

        return false;
    }
}
