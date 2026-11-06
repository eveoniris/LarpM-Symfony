<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApiClientRepository;
use App\Service\Api\ApiClientManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'api:client:revoke', description: 'Révoque une application tierce (plus d\'échange de jeton, rôles retirés)')]
final class ApiClientRevokeCommand extends Command
{
    public function __construct(
        private readonly ApiClientRepository $repository,
        private readonly ApiClientManager $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('client_id', InputArgument::REQUIRED, 'client_id de l\'application (voir api:client:list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $clientId */
        $clientId = $input->getArgument('client_id');

        $client = $this->repository->findOneByClientId($clientId);
        if (null === $client) {
            $io->error(\sprintf('Aucune application avec le client_id « %s ».', $clientId));

            return Command::FAILURE;
        }

        $this->manager->revoke($client);
        $io->success(\sprintf('Application « %s » révoquée.', $client->getName()));

        return Command::SUCCESS;
    }
}
