<?php

declare(strict_types=1);

namespace App\Tests\Functional\Discord;

use App\Service\Discord\DiscordApiException;
use App\Service\Discord\DiscordApiInterface;

/** API Discord en mémoire : rôles, salons et membres par guilde, réponses différées et créations enregistrées. */
final class FakeDiscordApi implements DiscordApiInterface
{
    /** @var list<string> */
    public array $interactionReplies = [];

    /** @var list<array{type: string, name: string, parent_id: string|null, overwrites: list<array{id: string, type: int, allow: string, deny: string}>}> */
    public array $created = [];

    private int $nextId = 700000000000000001;

    /**
     * @param array<string, array<string, list<string>>>                                                  $members
     * @param array<string, list<array{id: string, name: string}>>                                        $roles
     * @param list<array{id: string, name: string}>                                                       $guilds
     * @param array<string, list<array{id: string, name: string, type: int, parent_id: string|null}>>     $channels
     */
    public function __construct(
        public array $members,
        private readonly bool $fail = false,
        public array $roles = [],
        private readonly array $guilds = [],
        public array $channels = [],
        private readonly bool $failChannelCreation = false,
    ) {
    }

    public function memberRoleIds(string $guildId, string $userId): ?array
    {
        $this->failIfNeeded();

        return $this->members[$guildId][$userId] ?? null;
    }

    public function botGuilds(): array
    {
        $this->failIfNeeded();

        return $this->guilds;
    }

    public function guildRoles(string $guildId): array
    {
        $this->failIfNeeded();

        return $this->roles[$guildId] ?? [];
    }

    public function guildChannels(string $guildId): array
    {
        $this->failIfNeeded();

        return $this->channels[$guildId] ?? [];
    }

    public function createRole(string $guildId, string $name): string
    {
        $this->failIfNeeded();
        $id = (string) $this->nextId++;
        $this->roles[$guildId][] = ['id' => $id, 'name' => $name];
        $this->created[] = ['type' => 'role', 'name' => $name, 'parent_id' => null, 'overwrites' => []];

        return $id;
    }

    public function createChannel(string $guildId, string $name, int $type, ?string $parentId, array $permissionOverwrites): string
    {
        $this->failIfNeeded();
        if ($this->failChannelCreation) {
            throw new DiscordApiException('Discord a refusé la création (HTTP 403 : Missing Permissions).');
        }

        $id = (string) $this->nextId++;
        $this->channels[$guildId][] = ['id' => $id, 'name' => $name, 'type' => $type, 'parent_id' => $parentId];
        $this->created[] = [
            'type' => 4 === $type ? 'category' : 'channel',
            'name' => $name,
            'parent_id' => $parentId,
            'overwrites' => $permissionOverwrites,
        ];

        return $id;
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

    private function failIfNeeded(): void
    {
        if ($this->fail) {
            throw new DiscordApiException('Discord indisponible');
        }
    }
}
