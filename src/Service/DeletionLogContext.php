<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\LogActionType;
use WeakMap;

/**
 * Contexte métier optionnel d'une suppression.
 *
 * Le listener Doctrine (DeletionLogListener) écrit une entrée log_action pour chaque entité
 * supprimée. Un controller ou un service peut, avant le flush, annoter l'entité pour préciser
 * le type de log et ajouter des données : l'entrée reste unique, enrichie.
 */
final class DeletionLogContext
{
    /** @var WeakMap<object, array{type: ?LogActionType, extra: array<string, mixed>}> */
    private WeakMap $annotations;

    public function __construct()
    {
        $this->annotations = new WeakMap();
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function annotate(object $entity, ?LogActionType $type = null, array $extra = []): void
    {
        $existing = $this->annotations[$entity] ?? ['type' => null, 'extra' => []];

        $this->annotations[$entity] = [
            'type' => $type ?? $existing['type'],
            'extra' => [...$existing['extra'], ...$extra],
        ];
    }

    /**
     * @return array{type: ?LogActionType, extra: array<string, mixed>}|null
     */
    public function consume(object $entity): ?array
    {
        $annotation = $this->annotations[$entity] ?? null;
        unset($this->annotations[$entity]);

        return $annotation;
    }
}
