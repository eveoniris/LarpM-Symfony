<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

interface PlusBilletterieClientInterface
{
    /**
     * Retourne une page de participants d'un évènement.
     *
     * @return array{items: list<Attendee>, termine: bool, suivant: int} suivant = position à passer au prochain appel
     *
     * @throws PlusBilletterieException
     */
    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100): array;
}
