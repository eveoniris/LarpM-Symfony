<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Service\Discord\DiscordApiException;
use App\Service\Discord\DiscordApiInterface;

/** API Discord en mémoire : rôles des membres par guilde et réponses différées envoyées. */
final class FakeDiscordApi implements DiscordApiInterface
{
    /** @var list<string> */
    public array $interactionReplies = [];

    /** @param array<string, array<string, list<string>>> $members */
    public function __construct(
        public array $members,
        private readonly bool $fail = false,
    ) {
    }

    public function memberRoleIds(string $guildId, string $userId): ?array
    {
        if ($this->fail) {
            throw new DiscordApiException('Discord indisponible');
        }

        return $this->members[$guildId][$userId] ?? null;
    }

    public function addRole(string $guildId, string $userId, string $roleId): void
    {
        $this->members[$guildId][$userId][] = $roleId;
    }

    public function removeRole(string $guildId, string $userId, string $roleId): void
    {
        $this->members[$guildId][$userId] = array_values(array_diff($this->members[$guildId][$userId], [$roleId]));
    }

    public function editInteractionResponse(string $applicationId, string $interactionToken, string $content): void
    {
        $this->interactionReplies[] = $content;
    }
}
