<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converts every remaining utf8mb3/latin1 table (legacy from the Silex import) to
 * utf8mb4_unicode_ci. Fixes SQLSTATE 1366 "Incorrect string value" crashes when
 * users enter 4-byte characters (emoji) in free-text fields such as message.text.
 * Tables are discovered dynamically from information_schema, so this runs correctly
 * regardless of drift between environments, and is idempotent (already-utf8mb4 tables
 * are skipped on re-run).
 */
final class Version20260723080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert all non-utf8mb4 tables to utf8mb4_unicode_ci (fixes SQLSTATE 1366 on emoji/4-byte characters).';
    }

    public function up(Schema $schema): void
    {
        $tables = $this->connection->fetchFirstColumn(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             AND TABLE_COLLATION NOT LIKE 'utf8mb4%'"
        );

        foreach ($tables as $table) {
            try {
                $this->connection->executeStatement(
                    sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $table)
                );
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->warnIf(true, sprintf('Skipped charset conversion on %s: %s', $table, $e->getMessage()));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Irreversible: reverting to utf8mb3/latin1 could truncate 4-byte characters already stored.');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
