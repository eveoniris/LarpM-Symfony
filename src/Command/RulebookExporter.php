<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Rulebook\RulebookExporter as Exporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:rulebook:exporter', description: 'Régénère le cache local du rulebook (un fichier markdown par domaine dans docs/rulebook/)')]
class RulebookExporter extends Command
{
    public function __construct(
        private readonly Exporter $exporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('domaine', null, InputOption::VALUE_REQUIRED, 'Code d\'un seul domaine à régénérer (sinon tous)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domaine = $input->getOption('domaine');

        try {
            $fichiers = $this->exporter->exporter(\is_string($domaine) && '' !== $domaine ? $domaine : null);
        } catch (Throwable $exception) {
            $io->error('Export impossible : ' . $exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('%d fichier(s) écrit(s).', \count($fichiers)));
        $io->listing($fichiers);

        return Command::SUCCESS;
    }
}
