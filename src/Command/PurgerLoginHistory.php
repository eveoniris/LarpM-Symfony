<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\LoginHistoryRepository;
use App\Security\IpAnonymizer;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rétention de l'historique de connexion : anonymise les IP puis supprime les lignes anciennes.
 *
 * Simulation par défaut (aucune écriture), --apply pour appliquer. À lancer chaque jour en cron :
 *   php bin/console app:login-history:purger --apply
 */
#[AsCommand(name: 'app:login-history:purger', description: "Anonymise les IP de l'historique de connexion après 30 jours et supprime les lignes de plus de 12 mois")]
final class PurgerLoginHistory extends Command
{
    private const int DEFAULT_ANONYMIZE_AFTER_DAYS = 30;
    private const int DEFAULT_DELETE_AFTER_MONTHS = 12;

    public function __construct(
        private readonly LoginHistoryRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Appliquer les changements (simulation par défaut)')
            ->addOption('anonymiser-apres-jours', null, InputOption::VALUE_REQUIRED, "Âge en jours au-delà duquel l'IP est anonymisée", (string) self::DEFAULT_ANONYMIZE_AFTER_DAYS)
            ->addOption('supprimer-apres-mois', null, InputOption::VALUE_REQUIRED, 'Âge en mois au-delà duquel la ligne est supprimée', (string) self::DEFAULT_DELETE_AFTER_MONTHS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('anonymiser-apres-jours');
        $months = (int) $input->getOption('supprimer-apres-mois');
        if ($days < 1 || $months < 1) {
            $io->error('Les durées doivent être supérieures ou égales à 1.');

            return Command::INVALID;
        }

        $now = new DateTimeImmutable();
        $anonymizeBefore = $now->modify(\sprintf('-%d days', $days));
        $deleteBefore = $now->modify(\sprintf('-%d months', $months));
        $apply = (bool) $input->getOption('apply');

        // La suppression passe en premier : inutile d'anonymiser des lignes qui vont disparaître.
        $toDelete = $this->repository->countOlderThan($deleteBefore);
        $deleted = $apply ? $this->repository->deleteOlderThan($deleteBefore) : 0;

        $toAnonymize = $this->repository->countToAnonymize($anonymizeBefore);
        $anonymized = 0;
        if ($apply) {
            foreach ($this->repository->findIpsToAnonymize($anonymizeBefore) as $ip) {
                $anonymized += $this->repository->anonymizeIp($ip, IpAnonymizer::anonymize($ip), $anonymizeBefore);
            }
        }

        $io->definitionList(
            ['Mode' => $apply ? 'APPLIQUÉ' : 'SIMULATION (ajouter --apply pour appliquer)'],
            ['Suppression' => \sprintf('lignes de plus de %d mois (avant le %s)', $months, $deleteBefore->format('d/m/Y'))],
            ['Anonymisation' => \sprintf('IP de plus de %d jours (avant le %s)', $days, $anonymizeBefore->format('d/m/Y'))],
        );

        if ($apply) {
            $io->success(\sprintf('%d ligne(s) supprimée(s), %d IP anonymisée(s).', $deleted, $anonymized));
        } else {
            $io->note(\sprintf('%d ligne(s) seraient supprimées, %d IP seraient anonymisées.', $toDelete, $toAnonymize));
        }

        return Command::SUCCESS;
    }
}
