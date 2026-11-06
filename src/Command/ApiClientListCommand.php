<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApiClientRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'api:client:list', description: 'Liste les applications tierces de l\'API (jamais les secrets)')]
final class ApiClientListCommand extends Command
{
    public function __construct(
        private readonly ApiClientRepository $repository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($this->repository->findBy([], ['name' => 'ASC']) as $client) {
            $rows[] = [
                $client->getName(),
                $client->getClientId(),
                $client->isActive() ? 'active' : 'révoquée',
                implode(', ', $client->getUser()->getRoles()),
                null === $client->getGn() ? '(tous)' : \sprintf('#%d', (int) $client->getGn()->getId()),
                $client->getLastUsedAt()?->format('Y-m-d H:i') ?? 'jamais',
            ];
        }
        $io->table(['Nom', 'client_id', 'État', 'Rôles', 'GN', 'Dernier usage'], $rows);

        return Command::SUCCESS;
    }
}
