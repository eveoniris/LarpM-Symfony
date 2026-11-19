<?php

declare(strict_types=1);

namespace App\Service;

/** Message à envoyer aux joueurs concernés par un ajout en masse. */
final readonly class AjoutMasseMessage
{
    public function __construct(
        public string $texte,
        public bool $mail,
    ) {
    }
}
