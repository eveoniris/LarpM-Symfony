<?php

declare(strict_types=1);

namespace App\Service;

/** Résultat de la résolution d'une liste d'identifiants en joueurs à prévenir. */
final readonly class MessageMasseCibles
{
    /**
     * @param array<int, MessageMasseDestinataire> $destinataires indexés par id utilisateur
     * @param list<int>                            $sansUser      personnages sans joueur rattaché
     * @param list<int>                            $introuvables  identifiants qui ne correspondent à rien
     */
    public function __construct(
        public array $destinataires = [],
        public array $sansUser = [],
        public array $introuvables = [],
    ) {
    }

    /** Premier destinataire, utilisé pour l'aperçu. */
    public function premier(): ?MessageMasseDestinataire
    {
        $destinataires = $this->destinataires;

        return [] === $destinataires ? null : reset($destinataires);
    }
}
