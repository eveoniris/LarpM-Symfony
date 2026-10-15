<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\UnitOfWork;

/**
 * Repère les entités « supprimées » via deletedAt (null -> date) dans le flush en cours.
 */
final class SoftDeletionCollector
{
    /** Noms de champ rencontrés dans les entités (camelCase ou snake_case hérité). */
    private const array FIELDS = ['deletedAt', 'deleted_at'];

    /**
     * @param array<int, object> $hardDeletions entités déjà supprimées, indexées par spl_object_id
     *
     * @return array<int, object>
     */
    public function collect(UnitOfWork $uow, array $hardDeletions): array
    {
        $soft = [];

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (\array_key_exists(spl_object_id($entity), $hardDeletions) || !method_exists($entity, 'getDeletedAt')) {
                continue;
            }

            if ($this->becameDeleted($uow->getEntityChangeSet($entity))) {
                $soft[spl_object_id($entity)] = $entity;
            }
        }

        return $soft;
    }

    /**
     * @param array<string, mixed> $changeSet
     */
    private function becameDeleted(array $changeSet): bool
    {
        foreach (self::FIELDS as $field) {
            $change = $changeSet[$field] ?? null;

            if (\is_array($change) && null === ($change[0] ?? null) && null !== ($change[1] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
