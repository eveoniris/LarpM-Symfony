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
    name: 'app:gn-lock-give-sanctuaire-effect',
    description: 'Donne le niveau pratiquant de religion pour les PJ des groupes soumis aux effets de sanctuaire',
)]
class GnLockGiveSanctuaireEffect extends Command
{
    public function __construct(protected readonly PersonnageService $personnageService, protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Attribution des effets de sanctuaire');

        $progressBar = new ProgressBar($output);
        $progressBar->start();
        $i = 0;
        foreach ($this->personnageService->lockGnGiveSanctuaireGnEffect(true) as $i) {
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success(sprintf('Terminé pour %d personnage(s)', $i));
        return Command::SUCCESS;
    }
}
