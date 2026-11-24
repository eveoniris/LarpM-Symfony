<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\IpBloqueeRepository;
use App\Security\IpBlocklist;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rétention des adresses IP bloquées : supprime les blocages expirés depuis plus de 30 jours.
 *
 * Un blocage expiré reste visible un mois dans la liste d'administration (pour comprendre un refus récent),
 * puis la ligne disparaît. Simulation par défaut, --apply pour appliquer. À lancer chaque jour en cron :
 *   php bin/console app:ip-bloquee:purger --apply
 */
#[AsCommand(name: 'app:ip-bloquee:purger', description: 'Supprime les blocages d\'IP expirés depuis plus de 30 jours')]
final class PurgerIpBloquee extends Command
{
    private const int DEFAULT_DELETE_AFTER_DAYS = 30;

    public function __construct(
        private readonly IpBloqueeRepository $repository,
        private readonly IpBlocklist $ipBlocklist,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Appliquer les changements (simulation par défaut)')
            ->addOption('supprimer-apres-jours', null, InputOption::VALUE_REQUIRED, 'Nombre de jours après expiration au-delà duquel la ligne est supprimée', (string) self::DEFAULT_DELETE_AFTER_DAYS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('supprimer-apres-jours');
        if ($days < 1) {
            $io->error('La durée doit être supérieure ou égale à 1.');

            return Command::INVALID;
        }

        $before = (new DateTimeImmutable())->modify(\sprintf('-%d days', $days));
        $apply = (bool) $input->getOption('apply');

        $toDelete = $this->repository->countExpiredBefore($before);
        $deleted = $apply ? $this->repository->deleteExpiredBefore($before) : 0;
        if ($apply) {
            $this->ipBlocklist->invalidate();
        }

        $io->definitionList(['Mode' => $apply ? 'APPLIQUÉ' : 'SIMULATION (ajouter --apply pour appliquer)'], ['Suppression' => \sprintf(
            'blocages expirés avant le %s (%d jours)',
            $before->format('d/m/Y'),
            $days,
        )]);

        if ($apply) {
            $io->success(\sprintf('%d blocage(s) supprimé(s).', $deleted));
        } else {
            $io->note(\sprintf('%d blocage(s) seraient supprimés.', $toDelete));
        }

        return Command::SUCCESS;
    }
}
