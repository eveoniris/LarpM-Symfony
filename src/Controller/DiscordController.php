<?php

namespace App\Controller;

use Discord\Discord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    public static ?Discord $discord = null;

    private function getDiscord()
    {
        self::$discord ??= new Discord([
            'token' => $this->getParameter('discord.bot.token'),
            'loadAllMembers' => true,
        ]);

        return self::$discord;
    }

    #[Route('/discord/bot', name: 'discord.bot')]
    public function botAction(Request $request)
    {
        $this->getDiscord()?->on('ready', function ($discord) {
            echo "Bot is ready!", PHP_EOL;

            // Listen for messages
            $discord->on('message', function ($message) {
                echo "Received a message from {$message->author->username}: {$message->content}", PHP_EOL;

                // Respond to a specific command
                if ($message->content === '!hello') {
                    $message->channel->sendMessage('Hello, Discord!');
                }
            });
        });

        $this->getDiscord->run();
    }
}
