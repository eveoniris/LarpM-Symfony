<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Discord\DiscordApiClient;
use App\Service\Discord\DiscordApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Enregistre les slash commands du bot auprès de Discord (appel unique, à relancer si les commandes changent).
 */
#[AsCommand(name: 'discord:register-commands', description: 'Enregistre les slash commands du bot Discord (/roles)')]
final class DiscordRegisterCommands extends Command
{
    public function __construct(
        private readonly DiscordApiClient $api,
        #[Autowire('%discord.bot.id%')]
        private readonly string $applicationId,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->api->registerCommands($this->applicationId, [[
                'name' => 'roles',
                'description' => 'Synchroniser mes rôles Discord avec mon compte LarpManager',
                'type' => 1,
                'dm_permission' => false,
            ]]);
        } catch (DiscordApiException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Commande /roles enregistrée.');

        return Command::SUCCESS;
    }
}
