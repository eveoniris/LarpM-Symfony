<?php

declare(strict_types=1);

namespace App\Command;

use App\Security\AccesRefuseJournal;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Purge de var/log/error_acces.log : supprime les refus d'accès de plus de 31 jours.
 * Simulation par défaut, --apply pour appliquer (voir bin/cron-purge-logs.php).
 */
#[AsCommand(name: 'app:acces-log:purger', description: 'Supprime les lignes de error_acces.log de plus de 31 jours')]
final class PurgerAccesLog extends Command
{
    private const int DEFAULT_JOURS = 31;

    public function __construct(
        private readonly AccesRefuseJournal $journal,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Appliquer la suppression (simulation par défaut)')
            ->addOption('jours', null, InputOption::VALUE_REQUIRED, 'Âge en jours au-delà duquel la ligne est supprimée', (string) self::DEFAULT_JOURS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jours = (int) $input->getOption('jours');
        if ($jours < 1) {
            $io->error('Le nombre de jours doit être supérieur ou égal à 1.');

            return Command::INVALID;
        }

        $apply = (bool) $input->getOption('apply');
        $limite = new DateTimeImmutable(\sprintf('-%d days', $jours));
        $nombre = $apply ? $this->journal->purger($limite) : $this->journal->compter($limite);
        $io->success(\sprintf('%d ligne(s) %s (plus de %d jours).', $nombre, $apply ? 'supprimée(s)' : 'à supprimer (simulation)', $jours));

        return Command::SUCCESS;
    }
}
