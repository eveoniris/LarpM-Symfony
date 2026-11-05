<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\User;
use App\Repository\DiscordGuildRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Traite les interactions Discord (slash commands) : `/roles` synchronise les rôles de l'utilisateur dans la guilde courante.
 */
final readonly class DiscordInteractionHandler
{
    private const int TYPE_PING = 1;
    private const int TYPE_APPLICATION_COMMAND = 2;

    public function __construct(
        private DiscordGuildRepository $guilds,
        private EntityManagerInterface $entityManager,
        private DiscordSyncService $syncService,
        private DiscordApiInterface $api,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): ?DiscordInteractionResponse
    {
        return match ($payload['type'] ?? null) {
            self::TYPE_PING => DiscordInteractionResponse::pong(),
            self::TYPE_APPLICATION_COMMAND => $this->handleCommand($payload),
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private function handleCommand(array $payload): ?DiscordInteractionResponse
    {
        $name = \is_array($payload['data'] ?? null) ? $payload['data']['name'] ?? null : null;
        if ('roles' !== $name) {
            return null;
        }

        $guildId = \is_string($payload['guild_id'] ?? null) ? $payload['guild_id'] : null;
        $discordId = $payload['member']['user']['id'] ?? null;
        if (null === $guildId || !\is_string($discordId)) {
            return DiscordInteractionResponse::message('Cette commande s\'utilise sur un serveur Discord, pas en message privé.');
        }

        $guild = $this->guilds->findOneBy(['guildId' => $guildId]);
        if (null === $guild) {
            return DiscordInteractionResponse::message('Ce serveur n\'est pas configuré dans LarpManager.');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['discordId' => $discordId]);
        if (null === $user) {
            return DiscordInteractionResponse::message(\sprintf('Votre compte Discord n\'est pas lié à LarpManager. Liez-le ici (connexion requise) : %s', $this->urlGenerator->generate(
                'discord.lier',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )));
        }

        $applicationId = \is_string($payload['application_id'] ?? null) ? $payload['application_id'] : '';
        $token = \is_string($payload['token'] ?? null) ? $payload['token'] : '';

        return DiscordInteractionResponse::deferred(function () use ($user, $guild, $applicationId, $token): void {
            $content = $this->syncService->sync($user, $guild)->summary();

            try {
                $this->api->editInteractionResponse($applicationId, $token, $content);
            } catch (DiscordApiException $exception) {
                $this->logger->warning('Réponse différée Discord impossible : ' . $exception->getMessage());
            }
        });
    }
}
