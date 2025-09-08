<?php

namespace App\Command;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;
use App\Repository\GnRepository;
use App\Repository\ParticipantRepository;
use App\Service\PersonnageService;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:vieillir-personnages',
    description: 'Verrouille tout les groupes et joueur',
)]
class VieillirPersonnages extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager, private readonly PersonnageService $personnageService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('year', InputArgument::OPTIONAL, 'Year to add', default: 5)
            ->addArgument('dieage', InputArgument::OPTIONAL, 'Basic age to die from an old one without bonus', default: 60)
            ->addArgument('force', InputArgument::OPTIONAL, 'if script already done, force a rerun', default: false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vieillissement des personnages');

        foreach ($this->personnageService->vieillirTous(
            $input->getArgument('year'),
            $input->getArgument('dieage'),
            $input->getArgument('force')
        ) as $result) {
            if ($result instanceof Result) {
                $output->writeln(sprintf('%d Personnages vieillis', $result->rowCount()));
            } elseif ($result instanceof Personnage) {
                $output->writeln(sprintf('Le personnage %d est mort de vieillesse', $result->getId()));
            }
        }

        $io->success('Terminé');
        return Command::SUCCESS;
    }
}
