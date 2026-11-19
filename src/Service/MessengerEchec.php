<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

/** Un message en échec dans la file « failed » de Messenger. */
final readonly class MessengerEchec
{
    /** @param list<string> $destinataires adresses, quand le message est un e-mail */
    public function __construct(
        public int|string $id,
        public string $classe,
        public string $erreur,
        public ?DateTimeImmutable $date,
        public array $destinataires,
    ) {
    }
}
