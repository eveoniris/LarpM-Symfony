<?php

declare(strict_types=1);

namespace App\Service\Discord;

use SensitiveParameter;

/**
 * Appels à l'API Discord (bot) nécessaires à l'attribution des rôles.
 */
interface DiscordApiInterface
{
    /**
     * Rôles actuels d'un membre dans la guilde, ou null s'il n'est pas dans la guilde.
     *
     * @return list<string>|null
     *
     * @throws DiscordApiException
     */
    public function memberRoleIds(string $guildId, string $userId): ?array;

    /**
     * Guildes où le bot est présent.
     *
     * @return list<array{id: string, name: string}>
     *
     * @throws DiscordApiException
     */
    public function botGuilds(): array;

    /**
     * Rôles de la guilde (hors @everyone et rôles gérés par une intégration), triés par nom.
     *
     * @return list<array{id: string, name: string}>
     *
     * @throws DiscordApiException
     */
    public function guildRoles(string $guildId): array;

    /** @throws DiscordApiException */
    public function addRole(string $guildId, string $userId, string $roleId): void;

    /** @throws DiscordApiException */
    public function removeRole(string $guildId, string $userId, string $roleId): void;

    /**
     * Remplace le message différé d'une interaction (suite d'un accusé de réception de type 5).
     *
     * @throws DiscordApiException
     */
    public function editInteractionResponse(string $applicationId, #[SensitiveParameter] string $interactionToken, string $content): void;
}
