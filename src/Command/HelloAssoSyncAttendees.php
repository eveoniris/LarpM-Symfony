<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Service\HelloAsso\BilletSyncService;
use App\Service\HelloAsso\PlusBilletterieException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:helloasso:sync-attendees', description: 'Synchronise les participants HelloAsso Plus Billetterie d\'un GN (lecture seule, sans validation)')]
class HelloAssoSyncAttendees extends Command
{
    public function __construct(
        private readonly BilletSyncService $syncService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('gn', InputArgument::REQUIRED, 'Identifiant du GN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $gn = $this->entityManager->getRepository(Gn::class)->find((int) $input->getArgument('gn'));
        if (!$gn instanceof Gn) {
            $io->error('GN introuvable.');

            return Command::FAILURE;
        }

        $skip = 0;
        try {
            do {
                $result = $this->syncService->syncBatch($gn, $skip);
                $skip = $result['suivant'];
                $io->writeln(\sprintf('%d participant(s) traité(s)…', $skip));
            } while (!$result['termine']);
        } catch (PlusBilletterieException|\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('%d participant(s) synchronisé(s). Validez-les dans le tableau de rapprochement.', $skip));

        return Command::SUCCESS;
    }
}
