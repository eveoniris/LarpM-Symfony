<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Ce que dit la qualité (trois chiffres, en haut à droite) d'une étiquette : facture, nature, et si elle est spéciale.
 */
final readonly class EtiquetteQualite
{
    /**
     * @param list<string> $anomalies
     */
    public function __construct(
        public bool $speciale,
        public ?string $facture,
        public ?string $nature,
        public array $anomalies,
    ) {
    }
}
