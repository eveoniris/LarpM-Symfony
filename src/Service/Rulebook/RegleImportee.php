<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Enum\RulebookStatut;

/**
 * Règle lue dans le fichier de spec avant import en base.
 */
final readonly class RegleImportee
{
    // @mago-expect lint:excessive-parameter-list
    /**
     * @param list<string> $testsRefs
     */
    public function __construct(
        public string $domaineCode,
        public string $domaineTitre,
        public string $titre,
        public string $detail,
        public RulebookStatut $statut,
        /** Faux quand aucun marqueur de statut n'a été reconnu : le statut par défaut est alors à vérifier. */
        public bool $statutDetecte,
        public array $testsRefs,
    ) {
    }
}
