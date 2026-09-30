<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:gn-remove-learning', description: 'Retire une compétence d\'un personnage et nettoie les entrées XP')]
class GnRemoveLearning extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('personnage_id', InputArgument::REQUIRED, 'ID du personnage')
            ->addArgument('competence_id', InputArgument::REQUIRED, 'ID de la compétence à retirer')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans modification');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $personnageId = (int) $input->getArgument('personnage_id');
        $competenceId = (int) $input->getArgument('competence_id');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title(sprintf('Retrait de la compétence %d du personnage %d', $competenceId, $personnageId));

        $conn = $this->entityManager->getConnection();

        // Look up the competence family label for experience_gain matching
        $sql = 'SELECT cf.label AS famille_label, l.label AS niveau_label
                FROM competence c
                JOIN competence_family cf ON c.competence_family_id = cf.id
                LEFT JOIN level l ON c.level_id = l.id
                WHERE c.id = :cid';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('cid', $competenceId);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();

        if (!$row) {
            $io->error('Compétence ' . $competenceId . ' introuvable.');

            return Command::FAILURE;
        }

        $familleLabel = $row['famille_label'];
        $niveauLabel = $row['niveau_label'];
        $competenceLabel = trim($familleLabel . ' - ' . $niveauLabel);
        $io->note(sprintf('Compétence ciblée : %s', $competenceLabel));

        // Check personnage exists
        $stmt = $conn->prepare('SELECT id, xp FROM personnage WHERE id = :pid');
        $stmt->bindValue('pid', $personnageId);
        $personnage = $stmt->executeQuery()->fetchAssociative();

        if (!$personnage) {
            $io->error('Personnage ' . $personnageId . ' introuvable.');

            return Command::FAILURE;
        }

        $io->note(sprintf('XP actuel du personnage : %d', $personnage['xp'] ?? 0));

        // 1. Delete from personnages_competences
        $stmt = $conn->prepare('SELECT COUNT(*) FROM personnages_competences WHERE personnage_id = :pid AND competence_id = :cid');
        $stmt->bindValue('pid', $personnageId);
        $stmt->bindValue('cid', $competenceId);
        $countPc = (int) $stmt->executeQuery()->fetchOne();

        if ($countPc > 0) {
            $io->note(sprintf('personnages_competences : %d ligne(s) trouvée(s)', $countPc));
            if (!$dryRun) {
                $stmt = $conn->prepare('DELETE FROM personnages_competences WHERE personnage_id = :pid AND competence_id = :cid');
                $stmt->bindValue('pid', $personnageId);
                $stmt->bindValue('cid', $competenceId);
                $stmt->executeStatement();
                $io->text('  -> Supprimé');
            }
        } else {
            $io->note('personnages_competences : aucune entrée trouvée');
        }

        // 2. Delete from experience_usage
        $stmt = $conn->prepare('SELECT COUNT(*), COALESCE(SUM(xp_use), 0) FROM experience_usage WHERE personnage_id = :pid AND competence_id = :cid');
        $stmt->bindValue('pid', $personnageId);
        $stmt->bindValue('cid', $competenceId);
        $row = $stmt->executeQuery()->fetchNumeric();
        $countUsage = (int) $row[0];
        $sumUsage = (int) $row[1];

        if ($countUsage > 0) {
            $io->note(sprintf('experience_usage : %d ligne(s) trouvée(s), total xp_use = %d', $countUsage, $sumUsage));
            if (!$dryRun) {
                $stmt = $conn->prepare('DELETE FROM experience_usage WHERE personnage_id = :pid AND competence_id = :cid');
                $stmt->bindValue('pid', $personnageId);
                $stmt->bindValue('cid', $competenceId);
                $stmt->executeStatement();
                $io->text('  -> Supprimé');
            }
        } else {
            $io->note('experience_usage : aucune entrée trouvée');
        }

        // 3. Delete from experience_gain (match by explanation pattern)
        $pattern = '%Suppression de la compétence%' . $familleLabel . '%';
        $stmt = $conn->prepare('SELECT COUNT(*), COALESCE(SUM(xp_gain), 0) FROM experience_gain WHERE personnage_id = :pid AND explanation LIKE :pattern');
        $stmt->bindValue('pid', $personnageId);
        $stmt->bindValue('pattern', $pattern);
        $row = $stmt->executeQuery()->fetchNumeric();
        $countGain = (int) $row[0];
        $sumGain = (int) $row[1];

        if ($countGain > 0) {
            $io->note(sprintf('experience_gain : %d ligne(s) trouvée(s), total xp_gain = %d', $countGain, $sumGain));
            if (!$dryRun) {
                $stmt = $conn->prepare('DELETE FROM experience_gain WHERE personnage_id = :pid AND explanation LIKE :pattern');
                $stmt->bindValue('pid', $personnageId);
                $stmt->bindValue('pattern', $pattern);
                $stmt->executeStatement();
                $io->text('  -> Supprimé');
            }
        } else {
            $io->note('experience_gain : aucune entrée trouvée avec le motif "Suppression de la compétence"');
        }

        // 4. Soft-delete from personnage_apprentissage
        $stmt = $conn->prepare('SELECT COUNT(*) FROM personnage_apprentissage WHERE personnage_id = :pid AND competence_id = :cid AND deleted_at IS NULL');
        $stmt->bindValue('pid', $personnageId);
        $stmt->bindValue('cid', $competenceId);
        $countApp = (int) $stmt->executeQuery()->fetchOne();

        if ($countApp > 0) {
            $io->note(sprintf('personnage_apprentissage : %d ligne(s) trouvée(s)', $countApp));
            if (!$dryRun) {
                $stmt = $conn->prepare('UPDATE personnage_apprentissage SET deleted_at = NOW() WHERE personnage_id = :pid AND competence_id = :cid AND deleted_at IS NULL');
                $stmt->bindValue('pid', $personnageId);
                $stmt->bindValue('cid', $competenceId);
                $stmt->executeStatement();
                $io->text('  -> Soft-deleté');
            }
        } else {
            $io->note('personnage_apprentissage : aucune entrée active trouvée');
        }

        // 5. Recalculate XP
        if (!$dryRun) {
            $sql = 'UPDATE personnage
                    SET xp = (
                        COALESCE(
                            (SELECT SUM(eg.xp_gain)
                             FROM experience_gain eg
                             WHERE eg.personnage_id = :pid
                               AND eg.explanation NOT LIKE \'%Suppression de la compétence%\'),
                            0
                        )
                        -
                        COALESCE(
                            (SELECT SUM(eu.xp_use)
                             FROM experience_usage eu
                             WHERE eu.personnage_id = :pid),
                            0
                        )
                    )
                    WHERE id = :pid';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('pid', $personnageId);
            $stmt->executeStatement();

            $stmt = $conn->prepare('SELECT xp FROM personnage WHERE id = :pid');
            $stmt->bindValue('pid', $personnageId);
            $newXp = (int) $stmt->executeQuery()->fetchOne();

            $io->success(sprintf('Terminé. Nouveau XP du personnage %d : %d', $personnageId, $newXp));
        } else {
            $sql = 'SELECT
                        (
                            COALESCE((SELECT SUM(eg.xp_gain) FROM experience_gain eg WHERE eg.personnage_id = :pid AND eg.explanation NOT LIKE \'%Suppression de la compétence%\'), 0)
                            - COALESCE((SELECT SUM(eg2.xp_gain) FROM experience_gain eg2 WHERE eg2.personnage_id = :pid AND eg2.explanation LIKE :pattern), 0)
                        )
                        -
                        (
                            COALESCE((SELECT SUM(eu.xp_use) FROM experience_usage eu WHERE eu.personnage_id = :pid), 0)
                            - COALESCE((SELECT SUM(eu2.xp_use) FROM experience_usage eu2 WHERE eu2.personnage_id = :pid AND eu2.competence_id = :cid), 0)
                        ) AS nouveau_xp';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('pid', $personnageId);
            $stmt->bindValue('cid', $competenceId);
            $stmt->bindValue('pattern', $pattern);
            $newXp = (int) $stmt->executeQuery()->fetchOne();

            $io->note(sprintf('(dry-run) Nouveau XP simulé : %d', $newXp));
            $io->success('Dry-run terminé. Aucune modification effectuée.');
        }

        return Command::SUCCESS;
    }
}
