<?php

declare(strict_types=1);

namespace App\Command;

use Discord\Discord;
use Doctrine\ORM\EntityManagerInterface;
use SensitiveParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'discord:on', description: 'Lance le bot')]
class DiscordBot extends Command
{
    public static ?Discord $discord = null;

    private function getDiscord(): Discord
    {
        self::$discord ??= new Discord([
            'token' => $this->botToken,
            // 'loadAllMembers' => true,
        ]);

        return self::$discord;
    }

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly string $botId,
        #[SensitiveParameter]
        protected readonly string $botToken,
        #[SensitiveParameter]
        protected readonly string $apiKey,
        protected readonly string $oauthId,
        #[SensitiveParameter]
        protected readonly string $oauthSecret,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Bot Discord');

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

        $io->success('Terminé');

        return Command::SUCCESS;
    }
}
