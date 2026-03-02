<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PersonnageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:gn-lock-set-default-langue', description: 'Donne Les langues par défault')]
class GnLockSetDefaultLangue extends Command
{
    public function __construct(
        protected readonly PersonnageService $personnageService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Attribution langues par défaut');

        $this->personnageService->lockGnSetDefaultLangue();

        return Command::SUCCESS;
    }
}
