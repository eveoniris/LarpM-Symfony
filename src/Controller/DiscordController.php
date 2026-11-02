<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Discord\DiscordOAuthClient;
use App\Service\Discord\DiscordOAuthException;
use Discord\Discord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DiscordController extends AbstractController
{
    private const string SESSION_STATE = 'discord_oauth_state';

    public static ?Discord $discord = null;

    private function getDiscord(): Discord
    {
        self::$discord ??= new Discord([
            'token' => $this->getParameter('discord.bot.token'),
            //  'loadAllMembers' => true,
        ]);

        return self::$discord;
    }

    #[Route('/discord/bot', name: 'discord.bot')]
    public function botAction(Request $request): Response
    {
        $this->getDiscord()->on('ready', static function ($discord): void {
            echo 'Bot is ready!', \PHP_EOL;

            // Listen for messages
            $discord->on('message', static function ($message): void {
                echo "Received a message from {$message->author->username}: {$message->content}", \PHP_EOL;

                // Respond to a specific command
                if ('!hello' === $message->content) {
                    $message->channel->sendMessage('Hello, Discord!');
                }
            });
        });

        $this->getDiscord()->run();

        return new Response();
    }

    /**
     * Démarre la liaison du compte Discord de l'utilisateur connecté (OAuth2, scope identify).
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/lier', name: 'discord.lier', methods: ['GET'])]
    public function lierAction(Request $request, DiscordOAuthClient $oauth): Response
    {
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set(self::SESSION_STATE, $state);

        return $this->redirect($oauth->authorizeUrl($this->callbackUrl(), $state));
    }

    /**
     * Retour de Discord : vérifie le state, lit l'identité et l'enregistre sur le compte connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/callback', name: 'discord.callback', methods: ['GET'])]
    public function callbackAction(Request $request, DiscordOAuthClient $oauth): Response
    {
        $user = $this->getUser();
        $expectedState = (string) $request->getSession()->remove(self::SESSION_STATE);
        $state = $request->query->getString('state');
        $redirect = $this->redirectToRoute('user.edit', ['user' => $user->getId()]);

        if ('' === $expectedState || !hash_equals($expectedState, $state)) {
            $this->addFlash('alert', 'La liaison Discord a expiré ou est invalide, veuillez recommencer.');

            return $redirect;
        }

        $code = $request->query->getString('code');
        if ('' === $code || $request->query->has('error')) {
            $this->addFlash('alert', 'La liaison Discord a été annulée.');

            return $redirect;
        }

        try {
            $identity = $oauth->fetchIdentity($code, $this->callbackUrl());
        } catch (DiscordOAuthException $exception) {
            $this->logger->warning('Liaison Discord échouée : ' . $exception->getMessage());
            $this->addFlash('alert', 'Impossible de contacter Discord, veuillez réessayer plus tard.');

            return $redirect;
        }

        $other = $this->entityManager->getRepository(User::class)->findOneBy(['discordId' => $identity->id]);
        if (null !== $other && $other->getId() !== $user->getId()) {
            $this->addFlash('alert', 'Ce compte Discord est déjà lié à un autre compte LarpManager.');

            return $redirect;
        }

        $user->setDiscordId($identity->id);
        if (1 === preg_match('/^[a-z0-9_.]{2,32}$/i', $identity->username)) {
            $user->setDiscordPseudo($identity->username);
        }
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre compte Discord est lié.');

        return $redirect;
    }

    /**
     * Supprime la liaison (le pseudo reste, modifiable à la main).
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/discord/delier', name: 'discord.delier', methods: ['POST'])]
    public function delierAction(Request $request): Response
    {
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('discord_delier', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setDiscordId(null);
        $this->entityManager->flush();
        $this->addFlash('success', 'La liaison avec Discord a été supprimée.');

        return $this->redirectToRoute('user.edit', ['user' => $user->getId()]);
    }

    private function callbackUrl(): string
    {
        return $this->generateUrl('discord.callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    #[Route('/discord/result', name: 'discord.result')]
    public function resultAction(Request $request): Response
    {
        $this->logger->info('Discord result: ' . var_export($request->request->all(), true));

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/discord/interactions', name: 'discord.interactions')]
    public function interactionsAction(Request $request): Response
    {
        $this->logger->info('Discord interactions request: ' . var_export($request->getContent(), true));
        $this->logger->info('Discord interactions TYPE: ' . $request->request->get('type'));
        $this->logger->info('Discord interactions KEY: ' . $this->getParameter('discord.api.key'));

        /* TODO
         * $sign = $request->headers->get('x-signature-ed25519');
         * $time = $request->headers->get('x-signature-timestamp');
         * if (null === $sign || null === $time || '' !== trim($sign, '0..9A..Fa..f')) {
         * return new JsonResponse([], 401);
         * }
         *
         * $message = $time . $request->getContent();
         * $binarySign = sodium_hex2bin($sign);
         * $binaryKey = sodium_hex2bin($this->getParameter('discord.api.key')); // ERROR  Argument #1 ($string) must be a valid hexadecimal string
         *
         * if (!sodium_crypto_sign_verify_detached($binarySign, $message, $binaryKey)) {
         * return new JsonResponse([], 401);
         * }
         */

        return match ($request->request->get('type')) {
            1 => new JsonResponse(['type' => 1]),
            2 => new JsonResponse(['type' => 2]),
            default => new JsonResponse([], 400),
        };
    }

    #[Route('/discord/linked-role', name: 'discord.linked-role')]
    public function linkedRoleAction(Request $request): Response
    {
        $this->logger->warning('Discord linked role');

        return new JsonResponse(['ok' => true]);
    }
}
