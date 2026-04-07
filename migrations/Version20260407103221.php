<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407103221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema cleanup: personnages_connaissances drop legacy id/discr + composite PK + FKs, territoire_quete FKs, qr_code_scan_log item_id FK';
    }

    private function fkExists(string $table, string $constraintName): bool
    {
        return (bool) $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraintName]
        );
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    public function up(Schema $schema): void
    {
        // 1. personnages_connaissances: clean up legacy id/discr columns, rebuild as proper join table
        if ($this->columnExists('personnages_connaissances', 'id')) {
            // Deduplicate: keep the row with the lowest id for each (personnage_id, connaissance_id) pair
            $this->addSql(<<<'SQL'
                DELETE pc1 FROM personnages_connaissances pc1
                INNER JOIN personnages_connaissances pc2
                  ON pc1.personnage_id = pc2.personnage_id
                 AND pc1.connaissance_id = pc2.connaissance_id
                 AND pc1.id > pc2.id
                SQL);

            // Remove AUTO_INCREMENT first (required before dropping a PK column in MySQL)
            $this->addSql('ALTER TABLE personnages_connaissances MODIFY id INT UNSIGNED NOT NULL');

            // Drop legacy columns, fix connaissance_id nullability, swap PK to composite
            $this->addSql(<<<'SQL'
                ALTER TABLE personnages_connaissances
                  DROP id,
                  DROP discr,
                  CHANGE connaissance_id connaissance_id INT UNSIGNED NOT NULL,
                  DROP PRIMARY KEY,
                  ADD PRIMARY KEY (personnage_id, connaissance_id)
                SQL);
        }

        if (!$this->fkExists('personnages_connaissances', 'FK_3F5B4C1D5E315342')) {
            $this->addSql('ALTER TABLE personnages_connaissances ADD CONSTRAINT FK_3F5B4C1D5E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        }
        if (!$this->fkExists('personnages_connaissances', 'FK_3F5B4C1D68A34E8E')) {
            $this->addSql('ALTER TABLE personnages_connaissances ADD CONSTRAINT FK_3F5B4C1D68A34E8E FOREIGN KEY (connaissance_id) REFERENCES connaissance (id)');
        }

        // 2. territoire_quete: convert MyISAM → InnoDB (required for FK support), then add FKs
        $this->addSql('ALTER TABLE territoire_quete ENGINE=InnoDB');
        if (!$this->fkExists('territoire_quete', 'FK_63718DCD0F97A8')) {
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        }
        if (!$this->fkExists('territoire_quete', 'FK_63718DCB011823')) {
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCB011823 FOREIGN KEY (territoire_cible_id) REFERENCES territoire (id)');
        }

        // 3. qr_code_scan_log: fix item_id type and add FK
        $this->addSql('ALTER TABLE qr_code_scan_log CHANGE item_id item_id INT UNSIGNED DEFAULT NULL');
        if (!$this->fkExists('qr_code_scan_log', 'FK_4F1CE7B0126F525E')) {
            $this->addSql('ALTER TABLE qr_code_scan_log ADD CONSTRAINT FK_4F1CE7B0126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qr_code_scan_log DROP FOREIGN KEY FK_4F1CE7B0126F525E');
        $this->addSql('ALTER TABLE qr_code_scan_log CHANGE item_id item_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCD0F97A8');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCB011823');

        $this->addSql('ALTER TABLE personnages_connaissances DROP FOREIGN KEY FK_3F5B4C1D5E315342');
        $this->addSql('ALTER TABLE personnages_connaissances DROP FOREIGN KEY FK_3F5B4C1D68A34E8E');
        $this->addSql(<<<'SQL'
            ALTER TABLE personnages_connaissances
              DROP PRIMARY KEY,
              ADD id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
              ADD discr VARCHAR(255) NOT NULL DEFAULT 'extended',
              CHANGE connaissance_id connaissance_id INT UNSIGNED DEFAULT NULL
            SQL);
    }
}
