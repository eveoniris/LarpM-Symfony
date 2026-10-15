<?php

declare(strict_types=1);

namespace App\Service;

use Stringable;
use Throwable;

/**
 * Libellé lisible d'une entité supprimée (getLabel/getNom/getTitre/getLibelle, sinon __toString).
 */
final class DeletionLabelResolver
{
    private const array LABEL_METHODS = ['getLabel', 'getNom', 'getTitre', 'getLibelle'];

    private const int LABEL_MAX_LENGTH = 255;

    public function label(object $entity): ?string
    {
        foreach (self::LABEL_METHODS as $method) {
            $value = $this->call($entity, $method);

            if (\is_string($value) && '' !== $value) {
                return $this->truncate($value);
            }
        }

        if (!$entity instanceof Stringable) {
            return null;
        }

        $value = $this->call($entity, '__toString');

        return \is_string($value) ? $this->truncate($value) : null;
    }

    private function call(object $entity, string $method): mixed
    {
        if (!method_exists($entity, $method)) {
            return null;
        }

        try {
            return $entity->{$method}();
        } catch (Throwable) {
            return null;
        }
    }

    private function truncate(string $value): string
    {
        return mb_substr($value, start: 0, length: self::LABEL_MAX_LENGTH);
    }
}
