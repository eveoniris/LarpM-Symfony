<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Service\DepouillementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:depouillement:purger-simulations', description: 'Supprime les simulations de dépouillement d\'enveloppes (jamais les dépouillements réels)')]
class PurgerSimulationsDepouillement extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DepouillementService $depouillementService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('gn', null, InputOption::VALUE_REQUIRED, 'Limiter la purge à un GN (identifiant)');
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

        $io->success(\sprintf('%d simulation(s) supprimée(s).', $this->depouillementService->purgerSimulations($gn)));

        return Command::SUCCESS;
    }
}
