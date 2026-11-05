<?php

declare(strict_types=1);

namespace App\Service\Discord;

/** Identité Discord renvoyée par `GET /users/@me` après une autorisation OAuth. */
final readonly class DiscordIdentity
{
    public function __construct(
        public string $id,
        public string $username,
    ) {
    }
}
