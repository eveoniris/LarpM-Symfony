<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

/**
 * Résultat de l'import pour une règle : son code et si elle a été créée (ou ignorée car déjà en base).
 */
final readonly class ImportLigne
{
    public function __construct(
        public RegleImportee $regle,
        public string $code,
        public bool $creee,
    ) {
    }
}
