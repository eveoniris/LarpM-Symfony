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
    name: 'app:gn-lock-give-literature',
    description: 'Donne les bonus de litérature',
)]
class GnLockGiveLiterature extends Command
{
    public function __construct(protected readonly PersonnageService $personnageService, protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('gn', InputArgument::OPTIONAL, 'GN id if not the next session', default: null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Attribution des bonus de compétence littérature');

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Participant::class);

        if ($gnId = $input->getArgument('gn')) {
            $gn = $gnRepository->find($gnId);
        }

        $gn ??= $gnRepository->findNext();

        $total = 0;
        $total += $participantRepository->countAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, LevelType::INITIATED);
        $total += $participantRepository->countAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, LevelType::EXPERT);
        $total += $participantRepository->countAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, LevelType::MASTER);
        $total += $participantRepository->countAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, LevelType::GRAND_MASTER);
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();
        foreach ($this->personnageService->lockGnGiveLiteratureGnBonus($gn, true) as $i) {
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success(sprintf('Terminé pour %d personnage(s)', $total));
        return Command::SUCCESS;
    }
}
