<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Enum\DiscordProvisionStatus;

/**
 * Une ligne du rapport de provisionnement : le groupe, le numéro attribué, les noms créés et le résultat.
 */
final readonly class DiscordProvisionLine
{
    public function __construct(
        public string $groupe,
        public int $numero,
        public string $roleName,
        public string $channelName,
        public DiscordProvisionStatus $status,
        public string $detail = '',
    ) {
    }
}
