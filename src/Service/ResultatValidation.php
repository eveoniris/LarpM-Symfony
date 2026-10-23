<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Ce que la validation d'un dépouillement a appliqué : apprentissages, actions de fin d'Opus, report vers l'enveloppe suivante.
 */
final readonly class ResultatValidation
{
    public function __construct(
        public ResultatApprentissages $apprentissages,
        public ResultatApprentissages $finOpus,
        public ResultatApprentissages $enveloppeSuivante,
    ) {
    }

    /**
     * Les parties qui ont quelque chose à dire, dans l'ordre d'application.
     *
     * @return list<ResultatApprentissages>
     */
    public function parties(): array
    {
        return array_values(array_filter([$this->apprentissages, $this->finOpus, $this->enveloppeSuivante], static fn (ResultatApprentissages $partie): bool => !$partie->estVide()));
    }

    public function estVide(): bool
    {
        return [] === $this->parties();
    }

    public function aDesAnomalies(): bool
    {
        foreach ($this->parties() as $partie) {
            if ([] !== $partie->anomalies) {
                return true;
            }
        }

        return false;
    }

    public function resume(): string
    {
        return implode(' / ', array_map(static fn (ResultatApprentissages $partie): string => $partie->resume(), $this->parties()));
    }
}
