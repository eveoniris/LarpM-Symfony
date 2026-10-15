<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Distingue, parmi les entités supprimées d'un même flush, les racines (journalisées)
 * des entités supprimées en cascade (comptées dans la racine).
 */
final class DeletionTreeResolver
{
    private const int MAX_DEPTH = 10;

    /**
     * @param array<int, object> $deletions indexées par spl_object_id
     *
     * @return array{roots: array<int, object>, cascade: array<int, array<string, int>>}
     */
    public function resolve(EntityManagerInterface $em, array $deletions): array
    {
        $parents = $this->buildParentMap($em, $deletions);
        $roots = [];
        $cascade = [];

        foreach ($deletions as $id => $entity) {
            $rootId = $this->findRoot($id, $parents);

            if ($rootId === $id) {
                $roots[$id] = $entity;

                continue;
            }

            $class = $entity::class;
            $cascade[$rootId][$class] = ($cascade[$rootId][$class] ?? 0) + 1;
        }

        return ['roots' => $roots, 'cascade' => $cascade];
    }

    /**
     * Pour chaque entité supprimée, l'entité parente (association to-one) également supprimée.
     *
     * @param array<int, object> $deletions
     *
     * @return array<int, int>
     */
    private function buildParentMap(EntityManagerInterface $em, array $deletions): array
    {
        $parents = [];

        foreach ($deletions as $id => $entity) {
            $parentId = $this->findParentId($em, $id, $entity, $deletions);

            if (null !== $parentId) {
                $parents[$id] = $parentId;
            }
        }

        return $parents;
    }

    /**
     * @param array<int, object> $deletions
     */
    private function findParentId(EntityManagerInterface $em, int $id, object $entity, array $deletions): ?int
    {
        $meta = $em->getClassMetadata($entity::class);

        foreach ($meta->getAssociationNames() as $name) {
            if (!$meta->isSingleValuedAssociation($name)) {
                continue;
            }

            $related = $meta->getFieldValue($entity, $name);
            if (!\is_object($related)) {
                continue;
            }

            $relatedId = spl_object_id($related);
            if ($relatedId !== $id && \array_key_exists($relatedId, $deletions)) {
                return $relatedId;
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $parents
     */
    private function findRoot(int $id, array $parents): int
    {
        $visited = [$id => true];
        $current = $id;

        for ($depth = 0; $depth < self::MAX_DEPTH; ++$depth) {
            if (!\array_key_exists($current, $parents)) {
                return $current;
            }

            $current = $parents[$current];

            // Cycle (ex. User <-> Personnage) : l'entité de départ devient racine.
            if (\array_key_exists($current, $visited)) {
                return $id;
            }

            $visited[$current] = true;
        }

        return $current;
    }
}
