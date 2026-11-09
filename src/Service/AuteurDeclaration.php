<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;

/**
 * Qui enregistre une déclaration, et quand : le joueur (ses ajouts attendent une décision) ou le dépouillement (confirmé d'office).
 */
final readonly class AuteurDeclaration
{
    public function __construct(
        public bool $parJoueur,
        public int $utilisateur,
        public DateTimeInterface $le,
    ) {
    }
}
