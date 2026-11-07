<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

/**
 * Résultat de la vérification rulebook / tests.
 */
final readonly class RulebookVerification
{
    /**
     * @param list<string> $sansTest règles valides ou fixées sans aucun test (ni référence ni tag)
     * @param array<string, list<string>> $referencesCassees code de règle => références de tests introuvables
     * @param array<string, list<string>> $codesInconnus code cité par un test mais absent du rulebook => fichiers
     */
    public function __construct(
        public array $sansTest,
        public array $referencesCassees,
        public array $codesInconnus,
    ) {
    }

    /** Erreurs bloquantes en mode strict : références cassées et codes inconnus. */
    public function aDesErreurs(): bool
    {
        return [] !== $this->referencesCassees || [] !== $this->codesInconnus;
    }
}
