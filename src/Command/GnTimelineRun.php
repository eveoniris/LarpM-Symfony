<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GnTimeline\GnTimelineExecutor;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Point d'entrée du cron OVH : exécute les étapes de chronologie marquées « auto », échues et encore à faire.
 *
 * Sans `--apply`, la commande liste seulement les étapes concernées. Le vieillissement n'est jamais lancé par le cron.
 */
#[AsCommand(name: 'app:gn-timeline:run', description: 'Exécute les étapes automatiques échues de la chronologie des GN (simulation sans --apply)')]
final class GnTimelineRun extends Command
{
    public function __construct(
        private readonly GnTimelineExecutor $executor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Exécute réellement les étapes échues (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $etapes = $this->executor->echues(new DateTimeImmutable());

        $io->writeln(\sprintf('Chronologie des GN : %d étape(s) automatique(s) échue(s) (%s).', \count($etapes), $apply ? 'application' : 'simulation'));

        $echecs = 0;
        foreach ($etapes as $etape) {
            $libelle = \sprintf('#%d %s (%s, %s)', $etape->getId(), $etape->getLibelle(), $etape->getType()->value, $etape->getGn()?->getLabel());
            if (!$apply) {
                $io->writeln('  à exécuter : ' . $libelle);

                continue;
            }

            $resultat = $this->executor->executer($etape);
            $io->writeln(\sprintf('  %s %s : %s', $resultat['ok'] ? 'OK' : 'ÉCHEC', $libelle, $resultat['message']));
            $echecs += $resultat['ok'] ? 0 : 1;
        }

        return 0 === $echecs ? Command::SUCCESS : Command::FAILURE;
    }
}
