<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\ActionFinOpus;
use App\Enum\ActionStatut;

/**
 * Résultat du contrôle d'une action de fin d'Opus.
 */
final readonly class ActionFinOpusResultat
{
    /**
     * @param list<string> $anomalies à signaler au dépouilleur
     */
    public function __construct(
        public ActionFinOpus $action,
        public ActionStatut $statut,
        public int $elementsConserves,
        public array $anomalies,
    ) {
    }

    public function effet(): ?string
    {
        return ActionStatut::ACCEPTEE === $this->statut ? $this->action->effet() : null;
    }
}
