<?php

declare(strict_types=1);

namespace App\Service\Discord;

use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Client REST Discord avec le jeton du bot (pas de connexion Gateway : compatible hébergement mutualisé).
 */
final readonly class DiscordApiClient implements DiscordApiInterface
{
    private const string API = 'https://discord.com/api/v10';

    /** Au-delà, on n'attend pas la fin d'une limite de débit dans une requête web. */
    private const float MAX_RETRY_SECONDS = 2.0;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%discord.bot.token%')]
        #[SensitiveParameter]
        private string $botToken,
    ) {
    }

    public function memberRoleIds(string $guildId, string $userId): ?array
    {
        $response = $this->request('GET', \sprintf('/guilds/%s/members/%s', $guildId, $userId));

        if (404 === $response->getStatusCode()) {
            return null;
        }

        try {
            $roles = $response->toArray()['roles'] ?? [];
        } catch (ExceptionInterface $exception) {
            throw new DiscordApiException('Lecture du membre Discord impossible : ' . $exception->getMessage(), 0, $exception);
        }

        return array_values(array_map(strval(...), \is_array($roles) ? $roles : []));
    }

    public function botGuilds(): array
    {
        $response = $this->request('GET', '/users/@me/guilds?limit=200');

        try {
            $data = $response->toArray();
        } catch (ExceptionInterface $exception) {
            throw new DiscordApiException('Lecture des serveurs du bot impossible (token invalide ?) : ' . $exception->getMessage(), 0, $exception);
        }

        $guilds = [];
        foreach ($data as $guild) {
            if (\is_array($guild) && isset($guild['id'], $guild['name'])) {
                $guilds[] = ['id' => (string) $guild['id'], 'name' => (string) $guild['name']];
            }
        }
        usort($guilds, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $guilds;
    }

    public function guildRoles(string $guildId): array
    {
        $response = $this->request('GET', \sprintf('/guilds/%s/roles', $guildId));
        $status = $response->getStatusCode();
        if (403 === $status || 404 === $status) {
            throw new DiscordApiException('Le bot n\'est pas présent sur cette guilde, ou l\'identifiant de guilde est incorrect.');
        }

        try {
            $data = $response->toArray();
        } catch (ExceptionInterface $exception) {
            throw new DiscordApiException('Lecture des rôles Discord impossible : ' . $exception->getMessage(), 0, $exception);
        }

        $roles = [];
        foreach ($data as $role) {
            if (!\is_array($role) || !isset($role['id'], $role['name'])) {
                continue;
            }
            // @everyone porte l'identifiant de la guilde ; les rôles gérés (bots, boosters) ne s'attribuent pas.
            if ((string) $role['id'] === $guildId || !empty($role['managed'])) {
                continue;
            }
            $roles[] = ['id' => (string) $role['id'], 'name' => (string) $role['name']];
        }
        usort($roles, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $roles;
    }

    public function guildChannels(string $guildId): array
    {
        $response = $this->request('GET', \sprintf('/guilds/%s/channels', $guildId));

        try {
            $data = $response->toArray();
        } catch (ExceptionInterface $exception) {
            throw new DiscordApiException('Lecture des salons Discord impossible : ' . $exception->getMessage(), 0, $exception);
        }

        $channels = [];
        foreach ($data as $channel) {
            if (!\is_array($channel) || !isset($channel['id'], $channel['name'], $channel['type'])) {
                continue;
            }
            $channels[] = [
                'id' => (string) $channel['id'],
                'name' => (string) $channel['name'],
                'type' => (int) $channel['type'],
                'parent_id' => isset($channel['parent_id']) ? (string) $channel['parent_id'] : null,
            ];
        }

        return $channels;
    }

    public function createRole(string $guildId, string $name): string
    {
        return $this->createdId($this->request('POST', \sprintf('/guilds/%s/roles', $guildId), [
            'name' => $name,
            'permissions' => '0',
            'hoist' => false,
            'mentionable' => false,
        ]));
    }

    public function createChannel(string $guildId, string $name, int $type, ?string $parentId, array $permissionOverwrites): string
    {
        $payload = ['name' => $name, 'type' => $type, 'permission_overwrites' => $permissionOverwrites];
        if (null !== $parentId) {
            $payload['parent_id'] = $parentId;
        }

        return $this->createdId($this->request('POST', \sprintf('/guilds/%s/channels', $guildId), $payload));
    }

    public function addRole(string $guildId, string $userId, string $roleId): void
    {
        $this->assertSuccess($this->request('PUT', \sprintf('/guilds/%s/members/%s/roles/%s', $guildId, $userId, $roleId)));
    }

    public function removeRole(string $guildId, string $userId, string $roleId): void
    {
        $this->assertSuccess($this->request('DELETE', \sprintf('/guilds/%s/members/%s/roles/%s', $guildId, $userId, $roleId)));
    }

    public function editInteractionResponse(string $applicationId, #[SensitiveParameter] string $interactionToken, string $content): void
    {
        $this->assertSuccess($this->request('PATCH', \sprintf('/webhooks/%s/%s/messages/@original', $applicationId, $interactionToken), ['content' => $content]));
    }

    /**
     * Enregistre (remplace) les commandes globales de l'application.
     *
     * @param list<array<string, mixed>> $commands
     *
     * @throws DiscordApiException
     */
    public function registerCommands(string $applicationId, array $commands): void
    {
        $this->assertSuccess($this->request('PUT', \sprintf('/applications/%s/commands', $applicationId), $commands));
    }

    /** @param array<array-key, mixed>|null $json */
    private function request(string $method, string $path, ?array $json = null): ResponseInterface
    {
        $options = [
            'headers' => ['Authorization' => 'Bot ' . $this->botToken],
        ];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, self::API . $path, $options);
            if (429 === $response->getStatusCode()) {
                $retryAfter = (float) ($response->toArray(throw: false)['retry_after'] ?? (self::MAX_RETRY_SECONDS + 1));
                if ($retryAfter > self::MAX_RETRY_SECONDS) {
                    throw new DiscordApiException('Discord limite le débit des requêtes, réessayez dans quelques instants.');
                }
                usleep((int) ($retryAfter * 1_000_000));
                $response = $this->httpClient->request($method, self::API . $path, $options);
                $response->getStatusCode();
            }
        } catch (ExceptionInterface $exception) {
            throw new DiscordApiException('Échec de la communication avec Discord : ' . $exception->getMessage(), 0, $exception);
        }

        return $response;
    }

    private function createdId(ResponseInterface $response): string
    {
        $status = $response->getStatusCode();

        try {
            $body = $response->toArray(throw: false);
        } catch (ExceptionInterface) {
            $body = [];
        }

        if ($status < 200 || $status >= 300 || !isset($body['id'])) {
            $detail = isset($body['message']) && \is_string($body['message']) ? ' : ' . $body['message'] : '';

            throw new DiscordApiException(\sprintf(
                'Discord a refusé la création (HTTP %d%s). Vérifiez les permissions du bot (gérer les rôles, gérer les salons) et la position de son rôle.',
                $status,
                $detail,
            ));
        }

        return (string) $body['id'];
    }

    private function assertSuccess(ResponseInterface $response): void
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new DiscordApiException(\sprintf('Discord a refusé la modification des rôles (HTTP %d), vérifiez les permissions et la position du rôle du bot.', $status));
        }
    }
}
