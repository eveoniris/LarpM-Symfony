<?php

namespace App\Controller;

use Discord\Discord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    public static ?Discord $discord = null;

    private function getDiscord()
    {
        self::$discord ??= new Discord([
            'token' => $this->getParameter('discord.bot.token'),
            //  'loadAllMembers' => true,
        ]);

        return self::$discord;
    }

    #[Route('/discord/bot', name: 'discord.bot')]
    public function botAction(Request $request)
    {
        $this->getDiscord()?->on('ready', function ($discord) {
            echo 'Bot is ready!', PHP_EOL;

            // Listen for messages
            $discord->on('message', function ($message) {
                echo "Received a message from {$message->author->username}: {$message->content}", PHP_EOL;

                // Respond to a specific command
                if ('!hello' === $message->content) {
                    $message->channel->sendMessage('Hello, Discord!');
                }
            });
        });

        $this->getDiscord()?->run();
    }

    #[Route('/discord/result', name: 'discord.result')]
    public function resultAction(Request $request): Response
    {
        $this->logger->info('Discord result: '.var_export($request->request->all(), true));

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/discord/interactions', name: 'discord.interactions')]
    public function interactionsAction(Request $request): Response
    {
        $this->logger->info('Discord interactions request: '.var_export($request->getContent(), true));
        $this->logger->info('Discord interactions TYPE: '. $request->request->get('type'));
        $this->logger->info('Discord interactions KEY: '. $this->getParameter('discord.api.key'));

        /* TODO
        $sign = $request->headers->get('x-signature-ed25519');
        $time = $request->headers->get('x-signature-timestamp');
        if (null === $sign || null === $time || '' !== trim($sign, '0..9A..Fa..f')) {
            return new JsonResponse([], 401);
        }

        $message = $time . $request->getContent();
        $binarySign = sodium_hex2bin($sign);
        $binaryKey = sodium_hex2bin($this->getParameter('discord.api.key')); // ERROR  Argument #1 ($string) must be a valid hexadecimal string

        if (!sodium_crypto_sign_verify_detached($binarySign, $message, $binaryKey)) {
            return new JsonResponse([], 401);
        }
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
