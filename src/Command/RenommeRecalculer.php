<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Aligne la colonne personnage.renomme (lue par les statistiques) sur la somme de l'historique de renommée, pour les personnages
 * dont une soie a été validée avant le correctif (l'historique était écrit mais pas la colonne).
 */
#[AsCommand(name: 'app:renomme:recalculer', description: 'Aligne personnage.renomme sur l\'historique pour les soies validées avant le correctif (simulation par défaut)')]
class RenommeRecalculer extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Écrit réellement la colonne (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');

        $lignes = $this->connection->fetchAllAssociative('SELECT p.id, p.nom, p.renomme AS colonne, SUM(h.renomme) AS historique
             FROM personnage p
             JOIN renomme_history h ON h.personnage_id = p.id
             WHERE p.id IN (SELECT personnage_id FROM renomme_history WHERE explication LIKE :soie)
             GROUP BY p.id, p.nom, p.renomme
             HAVING COALESCE(p.renomme, 0) <> SUM(h.renomme)
             ORDER BY p.id', ['soie' => 'LH% - Soie']);

        if ([] === $lignes) {
            $io->success('Aucun écart : la colonne suit déjà l\'historique pour les personnages avec soie.');

            return Command::SUCCESS;
        }

        $io->table(['Personnage', 'Nom', 'Colonne', 'Historique'], array_map(static fn (array $l): array => [$l['id'], $l['nom'], $l['colonne'] ?? 'NULL', $l['historique']], $lignes));

        if (!$apply) {
            $io->note(\sprintf('Simulation : %d personnage(s) à corriger. Relancer avec --apply pour écrire.', \count($lignes)));

            return Command::SUCCESS;
        }

        foreach ($lignes as $ligne) {
            $this->connection->executeStatement('UPDATE personnage SET renomme = :valeur WHERE id = :id', ['valeur' => (int) $ligne['historique'], 'id' => $ligne['id']]);
        }

        $io->success(\sprintf('%d personnage(s) corrigé(s).', \count($lignes)));

        return Command::SUCCESS;
    }
}
