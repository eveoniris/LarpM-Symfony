<?php

namespace App\Command;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;
use App\Repository\GnRepository;
use App\Repository\ParticipantRepository;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'discord:on',
    description: 'Lance le bot',
)]
class Discord extends Command
{
    public static ?\Discord\Discord $discord = null;

    private function getDiscord()
    {
        self::$discord ??= new Discord([
            'token' => $this->botToken,
            'loadAllMembers' => true,
        ]);

        return self::$discord;
    }

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly string $botId,
        protected readonly string $botToken,
        protected readonly string $apiKey
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Bot Discord');

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

        $this->getDiscord()?->run();

        $io->success('Terminé');
        return Command::SUCCESS;
    }
}
