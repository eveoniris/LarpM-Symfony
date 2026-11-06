<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Api\ApiClientManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'api:client:create', description: 'Enregistre une application tierce de l\'API (le secret n\'est affiché qu\'une fois)')]
final class ApiClientCreateCommand extends Command
{
    public function __construct(
        private readonly ApiClientManager $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Nom de l\'application (ex. compagnon)')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Rôle accordé (répétable), ex. ROLE_API_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $name */
        $name = $input->getArgument('name');
        /** @var list<string> $roles */
        $roles = array_values(array_unique(array_map(strval(...), (array) $input->getOption('role'))));

        $result = $this->manager->create($name, $roles);

        $io->success(\sprintf('Application « %s » enregistrée.', $name));
        $io->definitionList(
            ['client_id' => $result['client']->getClientId()],
            ['client_secret' => $result['secret']],
            ['rôles' => [] === $roles ? '(aucun, ROLE_USER seulement)' : implode(', ', $roles)],
        );
        $io->warning('Notez le client_secret maintenant : il ne sera plus jamais affiché.');

        return Command::SUCCESS;
    }
}
