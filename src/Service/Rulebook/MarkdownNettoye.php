<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

/**
 * Résultat du nettoyage d'un texte markdown : le texte propre et le nombre de caractères indésirables retirés ou remplacés.
 */
final readonly class MarkdownNettoye
{
    public function __construct(
        public string $texte,
        public int $nettoyes,
    ) {
    }

    public function aEteModifie(): bool
    {
        return $this->nettoyes > 0;
    }
}
