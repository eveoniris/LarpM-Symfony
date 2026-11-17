<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Point d'entrée du cron OVH pour la timeline d'actions clés d'un GN (XP de participation, lock des groupes, sanctuaire,
 * vieillissement, notification des experts, gel du GN).
 *
 * Squelette : la timeline n'est pas encore codée (voir le plan gn-timeline), la commande ne fait donc rien. Elle existe pour que
 * le cron soit déjà planifié chez l'hébergeur. Quand les étapes existeront, elle n'exécutera que celles marquées « auto » et
 * échues, et `--apply` sera nécessaire pour écrire.
 */
#[AsCommand(name: 'app:gn-timeline:run', description: 'Exécute les étapes automatiques échues de la timeline des GN (squelette : ne fait rien pour le moment)')]
class GnTimelineRun extends Command
{
    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Exécute réellement les étapes échues (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln(\sprintf('Timeline des GN : aucune étape automatique définie (%s).', $input->getOption('apply') ? 'application' : 'simulation'));

        return Command::SUCCESS;
    }
}
