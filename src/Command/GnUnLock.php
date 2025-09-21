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
    name: 'app:gn-unlock',
    description: 'Déverrouille tout les groupes et joueur',
)]
class GnUnLock extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
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
        $io->title('Déverrouillage des modifications des groupes et joueurs du GN');

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);

        if ($gnId = $input->getArgument('gn')) {
            $gn = $gnRepository->find($gnId);
        }

        $gn ??= $gnRepository->findNext();
        $gnRepository->unlockAllGroup($gn);
        $io->success('Terminé');
        return Command::SUCCESS;
    }
}
