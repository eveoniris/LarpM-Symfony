<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DiscordGuildRepository;
use App\Service\Discord\DiscordBulkSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Aligne les rôles Discord de tous les comptes liés sur LarpManager (ajouts et retraits, rôles du mapping uniquement).
 *
 * À planifier une fois par jour : `php bin/console discord:sync --apply`. Sans --apply, simulation.
 */
#[AsCommand(name: 'discord:sync', description: 'Synchronise les rôles Discord de tous les comptes liés (simulation sans --apply)')]
final class DiscordSyncCommand extends Command
{
    public function __construct(
        private readonly DiscordGuildRepository $guilds,
        private readonly DiscordBulkSyncService $bulkSync,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('guild', null, InputOption::VALUE_REQUIRED, 'Identifiant Discord de la guilde (toutes les guildes configurées si absent)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Modifie réellement les rôles sur Discord (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $guildId = $input->getOption('guild');

        $guilds = null === $guildId ? $this->guilds->findAllOrdered() : array_filter([$this->guilds->findOneBy(['guildId' => (string) $guildId])]);
        if ([] === $guilds) {
            $io->error('Aucune guilde configurée correspondante (voir /admin/discord).');

            return Command::FAILURE;
        }

        $io->note($apply ? 'Mode réel : les rôles sont modifiés sur Discord.' : 'Simulation : aucun rôle n\'est modifié. Relancez avec --apply pour appliquer.');

        $failed = false;
        foreach ($guilds as $guild) {
            $io->section(\sprintf('%s (%s)', $guild->getNom(), $guild->getGuildId()));
            $report = $this->bulkSync->run($guild, $apply);

            if ([] !== $report->changes) {
                $io->table(['Joueur', 'Ajoutés', 'Retirés', 'Erreur'], array_map(static fn (array $change): array => [
                    $change['user'],
                    implode(', ', $change['added']),
                    implode(', ', $change['removed']),
                    $change['error'] ?? '',
                ], $report->changes));
            }

            $io->writeln(\sprintf(
                '%d compte(s) lié(s) traité(s) : %d déjà à jour, %d modifié(s), %d absent(s) du serveur, %d erreur(s).',
                $report->processed,
                $report->upToDate,
                \count($report->changes) - $report->errors,
                $report->absent,
                $report->errors,
            ));

            if ($report->aborted) {
                $io->error('Arrêt anticipé : plusieurs erreurs consécutives de Discord (token, permissions ou limite de débit).');
            }
            if ($report->errors > 0) {
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
