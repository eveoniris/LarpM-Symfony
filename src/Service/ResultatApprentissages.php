<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Ce que l'application d'une partie du retour de jeu (sorts, prières et potions, ou actions de fin d'Opus) a fait, ou ferait pour une simulation.
 */
final class ResultatApprentissages
{
    /** @var list<string> */
    public array $ajoutes = [];

    /** Déjà connus du personnage : ignorés. @var list<string> */
    public array $ignores = [];

    /** À vérifier par le scénariste, jamais bloquant. @var list<string> */
    public array $anomalies = [];

    /** Faux pour une simulation : rien n'a été écrit sur la fiche. */
    public bool $applique = false;

    public function __construct(
        private readonly string $intitule = 'Sorts, prières et potions',
        private readonly string $messageVide = 'Aucun sort, prière ni potion à appliquer.',
    ) {
    }

    public function estVide(): bool
    {
        return [] === $this->ajoutes && [] === $this->ignores && [] === $this->anomalies;
    }

    /**
     * Phrase unique pour l'historique du dépouillement.
     */
    public function resume(): string
    {
        $parties = [];
        if ([] !== $this->ajoutes) {
            $parties[] = ($this->applique ? 'ajoutés : ' : 'à ajouter (simulation) : ') . implode(', ', $this->ajoutes);
        }

        if ([] !== $this->ignores) {
            $parties[] = 'déjà connus : ' . implode(', ', $this->ignores);
        }

        if ([] !== $this->anomalies) {
            $parties[] = 'à vérifier : ' . implode(' ; ', $this->anomalies);
        }

        return [] === $parties ? $this->messageVide : $this->intitule . ' : ' . implode(' | ', $parties);
    }

    /**
     * @return array{ajoutes: list<string>, ignores: list<string>, anomalies: list<string>, applique: bool}
     */
    public function versTableau(): array
    {
        return ['ajoutes' => $this->ajoutes, 'ignores' => $this->ignores, 'anomalies' => $this->anomalies, 'applique' => $this->applique];
    }
}
