<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Enum\DepouillementStatut;
use App\Exception\ValidationInvalideException;
use App\Repository\DepouillementRepository;
use App\Service\DepouillementImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:depouillement:importer', description: 'Reporte dans l\'enveloppe du GN suivant les dépouillements validés en attente d\'import (jamais les simulations)')]
class ImporterDepouillements extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DepouillementRepository $depouillementRepository,
        private readonly DepouillementImportService $importService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('gn', null, InputOption::VALUE_REQUIRED, 'Limiter l\'import aux dépouillements d\'un GN (identifiant)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $gn = null;
        $gnId = $input->getOption('gn');
        if (null !== $gnId) {
            $gn = $this->entityManager->find(Gn::class, (int) $gnId);
            if (!$gn instanceof Gn) {
                $io->error(\sprintf('GN %s introuvable.', $gnId));

                return Command::FAILURE;
            }
        }

        $importes = 0;
        $enAttente = 0;
        foreach ($this->depouillementRepository->findEnAttenteImport($gn) as $depouillement) {
            try {
                $this->importService->importer($depouillement, null);
            } catch (ValidationInvalideException $exception) {
                $io->warning($exception->getMessage());

                continue;
            }

            DepouillementStatut::TERMINE === $depouillement->getStatut() ? ++$importes : ++$enAttente;
        }

        $io->success(\sprintf('%d dépouillement(s) importé(s), %d toujours en attente.', $importes, $enAttente));

        return Command::SUCCESS;
    }
}
