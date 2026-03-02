<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Repository\GnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:gn-participation-xp', description: 'Donne des XP de participation du dernier GN actif')]
class GnParticipationXp extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('gn', InputArgument::OPTIONAL, 'GN id if not the last session', default: null)->addArgument('xp', InputArgument::OPTIONAL, 'XP to give', default: 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);

        if ($gnId = $input->getArgument('gn')) {
            /** @var Gn $gn */
            $gn = $gnRepository->find($gnId);
        }

        $gn ??= $gnRepository->findCurrentActive();

        if (!$gn) {
            $io->success('Aucun GN trouvé');

            return Command::INVALID;
        }

        $io->title(\sprintf("Gains d'xp des personnages ayant participé au GN %s", $gn->getLabel()));

        $gnRepository->giveParticipationXp($gn, $input->getArgument('xp'));

        $io->success('Terminé');

        return Command::SUCCESS;
    }
}
