<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\LogActionType;
use App\Repository\LogActionRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rétention de log_action : supprime les logs de plus de 5 ans.
 *
 * Simulation par défaut (aucune écriture), --apply pour appliquer. À lancer en cron avec la purge
 * de login_history (voir bin/cron-purge-logs.php). Les types listés dans PROTECTED_TYPES ne sont
 * jamais supprimés : AGING_CHARACTERS sert de garde anti double vieillissement, le code lit sa
 * dernière ligne quelle que soit son ancienneté.
 */
#[AsCommand(name: 'app:log-action:purger', description: 'Supprime les logs de log_action de plus de 5 ans (hors types protégés)')]
final class PurgerLogAction extends Command
{
    private const int DEFAULT_DELETE_AFTER_YEARS = 5;
    private const int BATCH_SIZE = 5000;

    /** @var list<LogActionType> */
    private const array PROTECTED_TYPES = [LogActionType::AGING_CHARACTERS];

    public function __construct(
        private readonly LogActionRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Appliquer les suppressions (simulation par défaut)')
            ->addOption('supprimer-apres-annees', null, InputOption::VALUE_REQUIRED, 'Âge en années au-delà duquel le log est supprimé', (string) self::DEFAULT_DELETE_AFTER_YEARS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $years = (int) $input->getOption('supprimer-apres-annees');
        if ($years < 1) {
            $io->error('La durée doit être supérieure ou égale à 1 an.');

            return Command::INVALID;
        }

        $before = (new DateTimeImmutable())->modify(\sprintf('-%d years', $years));
        $protected = array_map(static fn (LogActionType $type): string => $type->value, self::PROTECTED_TYPES);
        $apply = (bool) $input->getOption('apply');

        $io->definitionList(
            ['Mode' => $apply ? 'APPLIQUÉ' : 'SIMULATION (ajouter --apply pour appliquer)'],
            ['Suppression' => \sprintf('logs de plus de %d an(s) (avant le %s)', $years, $before->format('d/m/Y'))],
            ['Types conservés' => implode(', ', $protected)],
        );

        if (!$apply) {
            $io->note(\sprintf('%d log(s) seraient supprimés.', $this->repository->countOlderThan($before, $protected)));

            return Command::SUCCESS;
        }

        $deleted = 0;
        do {
            $batch = $this->repository->deleteBatchOlderThan($before, $protected, self::BATCH_SIZE);
            $deleted += $batch;
        } while (self::BATCH_SIZE === $batch);

        $io->success(\sprintf('%d log(s) supprimé(s).', $deleted));

        return Command::SUCCESS;
    }
}
