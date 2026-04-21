<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix FK personnage_secondaire_id on user table.
 * The column references personnage(id), not personnage_secondaire(id).
 * Version20260304174243 had a bug in its UP method setting the wrong target table.
 */
final class Version20260421202856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix user.personnage_secondaire_id FK to reference personnage(id) instead of personnage_secondaire(id)';
    }

    private function fkExists(string $table, string $constraint): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );
    }

    private function addFk(string $table, string $constraint, string $sql): void
    {
        if (!$this->fkExists($table, $constraint)) {
            try {
                $this->connection->executeStatement($sql);
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->warnIf(true, sprintf('Skipped FK %s on %s (incompatible types or missing ref): %s', $constraint, $table, $e->getMessage()));
            }
        }
    }

    private function dropFk(string $table, string $constraint): void
    {
        if ($this->fkExists($table, $constraint)) {
            $this->connection->executeStatement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    public function up(Schema $schema): void
    {
        $this->dropFk('user', 'FK_8D93D649E6917FB3');
        $this->addFk('user', 'FK_8D93D649E6917FB3', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage (id)');
    }

    public function down(Schema $schema): void
    {
        $this->dropFk('user', 'FK_8D93D649E6917FB3');
        $this->addFk('user', 'FK_8D93D649E6917FB3', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage_secondaire (id)');
    }
}
