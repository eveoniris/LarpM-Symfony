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
    name: 'app:gn-lock-give-renomme',
    description: 'Donne les 2pts de renommé pour les nobles expert',
)]
class GnLockGiveRenomme extends Command
{
    public function __construct(protected readonly PersonnageService $personnageService, protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('gn', InputArgument::OPTIONAL, 'GN id if not the next session', default: null)
            ->addArgument('renomme', InputArgument::OPTIONAL, 'Number of renomme to give', default: 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Attribution des points de renommé');

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Participant::class);

        if ($gnId = $input->getArgument('gn')) {
            $gn = $gnRepository->find($gnId);
        }

        $gn ??= $gnRepository->findNext();

        $total = $participantRepository->countAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::NOBILITY, LevelType::EXPERT);
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        foreach ($this->personnageService->lockGnGiveNoblityGnRenomme($gn, $input->getArgument('renomme'), true) as $i) {
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success(sprintf('Terminé pour %d personnage(s)', $total));
        return Command::SUCCESS;
    }
}
