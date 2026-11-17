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
 * Rétention de log_action : anonymise les IP après 6 mois (période où les joueurs agissent encore sur
 * leurs données) et supprime les logs de plus de 5 ans.
 *
 * Simulation par défaut (aucune écriture), --apply pour appliquer. À lancer en cron avec la purge
 * de login_history (voir bin/cron-purge-logs.php). Les types listés dans PROTECTED_TYPES ne sont
 * jamais supprimés : AGING_CHARACTERS sert de garde anti double vieillissement, le code lit sa
 * dernière ligne quelle que soit son ancienneté.
 */
#[AsCommand(name: 'app:log-action:purger', description: 'Anonymise les IP de log_action après 6 mois et supprime les logs de plus de 5 ans (hors types protégés)')]
final class PurgerLogAction extends Command
{
    private const int DEFAULT_DELETE_AFTER_YEARS = 5;
    private const int DEFAULT_ANONYMIZE_AFTER_MONTHS = 6;
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
            ->addOption('supprimer-apres-annees', null, InputOption::VALUE_REQUIRED, 'Âge en années au-delà duquel le log est supprimé', (string) self::DEFAULT_DELETE_AFTER_YEARS)
            ->addOption('anonymiser-apres-mois', null, InputOption::VALUE_REQUIRED, "Âge en mois au-delà duquel l'IP est anonymisée", (string) self::DEFAULT_ANONYMIZE_AFTER_MONTHS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $years = (int) $input->getOption('supprimer-apres-annees');
        if ($years < 1) {
            $io->error('La durée doit être supérieure ou égale à 1 an.');

            return Command::INVALID;
        }

        $months = (int) $input->getOption('anonymiser-apres-mois');
        if ($months < 1) {
            $io->error("Le délai d'anonymisation doit être supérieur ou égal à 1 mois.");

            return Command::INVALID;
        }

        $now = new DateTimeImmutable();
        $before = $now->modify(\sprintf('-%d years', $years));
        $anonymizeBefore = $now->modify(\sprintf('-%d months', $months));
        $protected = array_map(static fn (LogActionType $type): string => $type->value, self::PROTECTED_TYPES);
        $apply = (bool) $input->getOption('apply');

        $io->definitionList(
            ['Mode' => $apply ? 'APPLIQUÉ' : 'SIMULATION (ajouter --apply pour appliquer)'],
            ['Suppression' => \sprintf('logs de plus de %d an(s) (avant le %s)', $years, $before->format('d/m/Y'))],
            ['Anonymisation' => \sprintf('IP de plus de %d mois (avant le %s)', $months, $anonymizeBefore->format('d/m/Y'))],
            ['Types conservés' => implode(', ', $protected)],
        );

        if (!$apply) {
            $io->note(\sprintf(
                '%d log(s) seraient supprimés, %d IP seraient anonymisées.',
                $this->repository->countOlderThan($before, $protected),
                $this->repository->countIpsToAnonymize($anonymizeBefore),
            ));

            return Command::SUCCESS;
        }

        $deleted = 0;
        do {
            $batch = $this->repository->deleteBatchOlderThan($before, $protected, self::BATCH_SIZE);
            $deleted += $batch;
        } while (self::BATCH_SIZE === $batch);

        // Après la suppression : inutile d'anonymiser des lignes qui viennent de disparaître.
        $io->success(\sprintf('%d log(s) supprimé(s), %d IP anonymisée(s).', $deleted, $this->repository->anonymizeIpsOlderThan($anonymizeBefore)));

        return Command::SUCCESS;
    }
}
