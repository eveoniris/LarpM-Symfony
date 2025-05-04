<?php

namespace App\Service;

use App\Entity\CompetenceFamily;
use App\Entity\Groupe;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;

/**
 * Sample complex conditions.
 *
 * How to "give" langue "1" if the entity is classe 21 AND religion 12
 * OR if the entity is classe 21 AND religion 5
 *
 * "OR" and "AND" apply on their following value array
 *
 *  Each condition MUST HAVE a TYPE between CLASSE, COMPETENCE, RELIGION, LANGUE, ORIGINE
 *  Each condition MUST have a VALUE as int or string like Religion ID or Competence Enum Type value
 *
 * PHP
 * $array = [
 *   'langue' => [
 *      'id' => 1,
 *      'conditions' => [
 *          'OR',
 *          [
 *              'AND',
 *              [
 *                'TYPE' => 'CLASSE',
 *                'VALUE' => 21
 *              ],
 *              [
 *                'TYPE' => 'RELIGION'
 *                'VALUE' => 12
 *              ],
 *          ],
 *          [
 *              'AND',
 *              [
 *                 'TYPE' => 'CLASSE',
 *                 'VALUE' => 21
 *               ],
 *               [
 *                 'TYPE' => 'RELIGION'
 *                 'VALUE' => 5
 *               ],
 *          ],
 *       ],
 *    ],
 * ];
 *
 *
 * langue is optional here, and the conditions can be the root.
 *
 * En JSON Dans celui-ci on va donner l'une OU l'autre competence qui résoudrons la/les conditions
 * {"COMPETENCE":[[{"VALUE":"TEXT TO DISPLAY FOR THIS COMPETENCE","conditions":["AND",{"TYPE":"CLASSE","VALUE":21},{"TYPE":"RELIGION","VALUE":12}]},{"VALUE":"OTHER TEXTE","conditions":["AND",{"TYPE":"CLASSE","VALUE":21},{"TYPE":"RELIGION","VALUE":5}]}]]}
 */
class ConditionsService
{
    public function isAllConditionsValid(Groupe|Personnage|CompetenceFamily $entity, array $list): bool
    {
        if (empty($list)) {
            return true;
        }
        foreach ($list as $key => $row) {
            if (
                in_array(
                    $key,
                    ['condition', 'conditions'],

                    true,
                ) && !$this->isValidConditions(
                    $entity,
                    $row,
                )) {
                return false;
            }

            if (is_array($row) && !$this->isAllConditionsValid($entity, $row)) {
                return false;
            }
        }

        return true;
    }

    public function isValidConditions(
        Groupe|Personnage|CompetenceFamily $entity,
        array $conditions,
        mixed $service = null,
        bool $isDataSet = false, // find and pick conditions in the "conditions" Array data
    ): bool
    {
        return null !== $this->getValidConditions($entity, $conditions, $service, $isDataSet);
    }

    public function getValidConditions(
        Groupe|Personnage|CompetenceFamily $entity,
        array $conditions,
        mixed $service = null,
        bool $isDataSet = false,
    ): ?array {
        if ($isDataSet) {
            $conditions = $this->getConditionsFromDataset($conditions);
        }

        if (empty($conditions)) {
            return null;
        }

        // For 1 condition without array
        if ($this->isKey('type', $conditions)) {
            $conditions = [$conditions];
        }

        $mode = 'AND';
        foreach ($conditions as $key => $condition) {
            // Par défaut les conditions sont des AND
            if (is_string($condition) && 'OR' === strtoupper($condition)) {
                $mode = 'OR';
                continue;
            }

            if (is_string($condition) && 'AND' === strtoupper($condition)) {
                $mode = 'AND';
                continue;
            }

            // for some short condition data
            if (!is_array($condition)) {
                $condition = ['type' => $key, 'value' => $condition];
            }

            if ($this->isValidCondition($entity, $condition, $service)) {
                // First OR mean TRUE
                if ('OR' === $mode) {
                    return $conditions;
                }
            } elseif ('AND' === $mode) {
                // In AND mode any false condition, mean FALSE for all
                return null;
            }
        }

        return $conditions;
    }

    /** Return the current data as a whole condition or the condition key of the dataset */
    public function getConditionsFromDataset(array $data): array
    {
        return $this->getKeyValue('condition', $data) ?? [];
    }

    public function getKeyValue(string $key, array $data): mixed
    {
        foreach ([$key, $key.'s', rtrim($key, 's')] as $multiKey) {
            foreach ([$multiKey, strtolower($multiKey), strtoupper($multiKey)] as $k) {
                if ($data[$k] ?? false) {
                    return $data[$k];
                }
            }
        }

        return null;
    }

    public function isKey(string $key, array $data): mixed
    {
        foreach ([$key, $key.'s', rtrim($key, 's')] as $multiKey) {
            foreach ([$multiKey, strtolower($multiKey), strtoupper($multiKey)] as $k) {
                if ($data[$k] ?? false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isValidCondition(
        Groupe|Personnage|CompetenceFamily $entity,
        array $condition,
        mixed $service = null,
    ): bool {
        // condition non testable
        if (!$this->isKey('type', $condition) || !$this->isKey('value', $condition)) {
            return false;
        }

        // Le personnage doit avoir cette origine pour que le bonus soit actif
        if (
            $entity instanceof Personnage
            && 'ORIGINE' === $this->getConditionType($condition)
            && $entity->getOrigine()?->getId() === (int) $this->getConditionValue($condition)
        ) {
            return true;
        }

        // Parmi les langues "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'LANGUE' === $this->getConditionType($condition)) {
            $hasRequired = false;
            /** @var PersonnageLangues $languePersonnage */
            foreach ($entity->getPersonnageLangues() as $languePersonnage) {
                if ($languePersonnage->getLangue()?->getId() === (int) $this->getConditionValue($condition)) {
                    // Do not return yet : if personnage had already the bonus langue
                    $hasRequired = true;
                }
            }
            if ($hasRequired) {
                return true;
            }
        }

        // Parmi les competences du personnage
        if ($entity instanceof Personnage && 'COMPETENCE' === $this->getConditionType($condition)) {
            if (is_numeric($this->getConditionValue($condition))) {
                return $entity->hasCompetenceId((int) $this->getConditionValue($condition));
            }

            return $entity->hasCompetenceLevel(
                CompetenceFamilyType::tryFrom($this->getConditionValue($condition)),
                LevelType::tryFrom($this->getKeyValue('level', $condition)),
            );
        }

        // Parmi les familles de competence du personnage
        if ($entity instanceof Personnage && 'COMPETENCE_FAMILLE' === $this->getConditionType($condition)) {
            if ($service instanceof CompetenceService) {
                if ($entity->getId() === (int) $this->getConditionValue($condition)) {
                    return true;
                }

                if (is_numeric($this->getConditionValue($condition)) && $service->getCompetence()
                        ->getCompetenceFamily()
                        ?->getId() === (int) $this->getConditionValue($condition)) {
                    return true;
                }

                if (strtoupper(
                        $service->getCompetence()
                            ->getCompetenceFamily()
                            ?->getCompetenceFamilyType()?->value,
                    ) === strtoupper($this->getConditionValue($condition))) {
                    return true;
                }
            }
        }

        // Parmi les familles de competence de la compétence
        if ($entity instanceof CompetenceFamily && 'COMPETENCE_FAMILLE' === $this->getConditionType($condition)) {
            if ($entity->getId() === (int) $this->getConditionValue($condition)) {
                return true;
            }

            if (
                strtoupper($entity->getCompetenceFamilyType()?->value) === $this->getConditionValue($condition)
            ) {
                return true;
            }

            if ($service instanceof CompetenceService) {
                if (is_numeric($this->getConditionValue($condition)) && $service->getCompetence()
                        ->getCompetenceFamily()
                        ?->getId() === (int) $this->getConditionValue($condition)) {
                    return true;
                }

                if (strtoupper(
                        $service->getCompetence()
                            ->getCompetenceFamily()
                            ?->getCompetenceFamilyType()?->value,
                    ) === $this->getConditionValue($condition)) {
                    return true;
                }
            }
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'RELIGION' === $this->getConditionType($condition)) {
            return $entity->hasReligionId(
                (int) $this->getConditionValue($condition),
                $this->getKeyValue('level', $condition),
            );
        }

        // Parmi les religions "basique" du personnage (sinon boucle infinie)
        if ($entity instanceof Personnage && 'CLASSE' === $this->getConditionType($condition)) {
            return $entity->getClasse()->getId() === (int) $this->getConditionValue($condition);
        }

        return false;
    }

    public function getConditionType(array $condition): string
    {
        return strtoupper($this->getKeyValue('type', $condition) ?? '');
    }

    public function getConditionValue(array $condition): string
    {
        return strtoupper($this->getKeyValue('value', $condition) ?? '');
    }
}
