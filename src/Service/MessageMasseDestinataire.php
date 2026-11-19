<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use App\Entity\User;

/** Un joueur à prévenir, avec les personnages de la liste qui le concernent. */
final readonly class MessageMasseDestinataire
{
    /** @param list<Personnage> $personnages vide quand la liste saisie contient directement des utilisateurs */
    public function __construct(
        public User $user,
        public array $personnages = [],
    ) {
    }
}
