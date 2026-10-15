<?php

declare(strict_types=1);

namespace App\Service;

use BackedEnum;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Stringable;
use Throwable;

/**
 * Construit les données (identifiant, libellé, snapshot) d'une entité sur le point d'être supprimée,
 * dans un format sérialisable en JSON pour log_action.
 */
final class DeletionSnapshotBuilder
{
    public function __construct(
        private readonly DeletionLabelResolver $labels,
    ) {
    }

    public function identifier(EntityManagerInterface $em, object $entity): int|string|null
    {
        $ids = $em->getUnitOfWork()->getEntityIdentifier($entity);
        $value = reset($ids);

        if (false === $value || null === $value) {
            return null;
        }

        return \is_int($value) ? $value : (string) $value;
    }

    public function label(object $entity): ?string
    {
        return $this->labels->label($entity);
    }

    /**
     * Données complètes de l'entrée log_action d'une suppression.
     *
     * @param array<string, mixed> $extra   données techniques (soft, cascade)
     * @param array<string, mixed> $context contexte métier annoté par l'appelant
     *
     * @return array<string, mixed>
     */
    public function payload(EntityManagerInterface $em, object $entity, array $extra, array $context): array
    {
        $data = [
            'class' => $entity::class,
            'id' => $this->identifier($em, $entity),
            'label' => $this->label($entity),
            'snapshot' => $this->snapshot($em, $entity),
            ...$extra,
        ];

        if ([] !== $context) {
            $data['context'] = $this->sanitize($context);
        }

        return $data;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function snapshot(EntityManagerInterface $em, object $entity): array
    {
        $logged = $this->toLog($entity);
        if (null !== $logged) {
            return $this->sanitize($logged);
        }

        $meta = $em->getClassMetadata($entity::class);
        $snapshot = [];

        foreach ($meta->getFieldNames() as $field) {
            $snapshot[$field] = $meta->getFieldValue($entity, $field);
        }

        foreach ($meta->getAssociationNames() as $name) {
            if (!$meta->isSingleValuedAssociation($name)) {
                continue;
            }

            $related = $meta->getFieldValue($entity, $name);
            if (\is_object($related)) {
                $snapshot[$name . '_id'] = $this->identifier($em, $related);
            }
        }

        return $this->sanitize($snapshot);
    }

    /**
     * Rend les valeurs sérialisables en JSON (dates, enums, objets).
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    public function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            $clean[$key] = match (true) {
                null === $value, \is_scalar($value) => $value,
                $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
                $value instanceof BackedEnum => $value->value,
                \is_array($value) => $this->sanitize($value),
                $value instanceof Stringable => (string) $value,
                default => null,
            };
        }

        return $clean;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function toLog(object $entity): ?array
    {
        if (!method_exists($entity, 'toLog')) {
            return null;
        }

        try {
            $logged = $entity->toLog();
        } catch (Throwable) {
            // toLog() peut déclencher un chargement paresseux en échec : repli sur le snapshot générique
            return null;
        }

        return \is_array($logged) ? $logged : null;
    }
}
