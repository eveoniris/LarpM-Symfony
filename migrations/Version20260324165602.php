<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324165602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema alignment phase 2: FK constraints, description_membres LONGTEXT, personnages_connaissances PK fix, messenger_messages index, religion.secret nullable, signed INT IDs';
    }

    /**
     * Returns 'int unsigned' or 'int' for the given table's id column.
     * Used to match FK referencing columns to their referenced column type.
     */
    private function idType(string $table): string
    {
        return (string) $this->connection->fetchOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'id'",
            [$table]
        );
    }

    /**
     * Returns true if a FK constraint with the given name already exists on the given table.
     */
    private function fkExists(string $table, string $constraintName): bool
    {
        return (bool) $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraintName]
        );
    }

    public function up(Schema $schema): void
    {
        // Disable strict mode to allow existing 0000-00-00 datetime values during table rebuilds
        $this->addSql("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
        $this->addSql('ALTER TABLE groupe CHANGE description_membres description_membres LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE groupe_gn CHANGE agents agents INT NOT NULL, CHANGE bateaux bateaux INT NOT NULL, CHANGE sieges sieges INT NOT NULL, CHANGE initiative initiative INT NOT NULL');
        $this->addSql('ALTER TABLE log_action CHANGE data data JSON DEFAULT NULL');
        // personnages_connaissances PK change skipped: duplicate (personnage_id, connaissance_id) pairs exist in data
        // Requires manual deduplication before applying

        // For each FK addition, if the referenced table's id is UNSIGNED on this DB,
        // the referencing column must be changed to UNSIGNED first (MySQL 8 strict FK type matching).
        if (!$this->fkExists('personnages_lignee', 'FK_E0C9F0802B3F22E1')) {
            if (str_contains($this->idType('lignees'), 'unsigned')) {
                $this->addSql('ALTER TABLE personnages_lignee CHANGE lignee_id lignee_id INT UNSIGNED DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE personnages_lignee CHANGE lignee_id lignee_id INT DEFAULT NULL');
            }
            $this->addSql('ALTER TABLE personnages_lignee ADD CONSTRAINT FK_E0C9F0802B3F22E1 FOREIGN KEY (lignee_id) REFERENCES lignees (id)');
        }

        if (!$this->fkExists('relecture', 'FK_FC5CF714631F6BDE')) {
            if (str_contains($this->idType('intrigue'), 'unsigned')) {
                $this->addSql('ALTER TABLE relecture CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
            }
            $this->addSql('ALTER TABLE relecture ADD CONSTRAINT FK_FC5CF714631F6BDE FOREIGN KEY (intrigue_id) REFERENCES intrigue (id)');
        }

        $this->addSql('ALTER TABLE religion CHANGE secret secret TINYINT DEFAULT 0');

        if (!$this->fkExists('technologie', 'FK_AD813674F7EB2017')) {
            if (str_contains($this->idType('competence_family'), 'unsigned')) {
                $this->addSql('ALTER TABLE technologie CHANGE competence_family_id competence_family_id INT UNSIGNED DEFAULT NULL');
            }
            $this->addSql('ALTER TABLE technologie ADD CONSTRAINT FK_AD813674F7EB2017 FOREIGN KEY (competence_family_id) REFERENCES competence_family (id)');
        }

        if (!$this->fkExists('territoire_quete', 'FK_63718DCD0F97A8')) {
            if (str_contains($this->idType('territoire'), 'unsigned')) {
                $this->addSql('ALTER TABLE territoire_quete CHANGE territoire_id territoire_id INT UNSIGNED NOT NULL, CHANGE territoire_cible_id territoire_cible_id INT UNSIGNED DEFAULT NULL');
            }
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        }
        if (!$this->fkExists('territoire_quete', 'FK_63718DCB011823')) {
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCB011823 FOREIGN KEY (territoire_cible_id) REFERENCES territoire (id)');
        }

        if (!$this->fkExists('territoire_has_loi', 'FK_5470401BAB82AB5')) {
            if (str_contains($this->idType('loi'), 'unsigned')) {
                $this->addSql('ALTER TABLE territoire_has_loi CHANGE loi_id loi_id INT UNSIGNED NOT NULL');
            }
            $this->addSql('ALTER TABLE territoire_has_loi ADD CONSTRAINT FK_5470401BAB82AB5 FOREIGN KEY (loi_id) REFERENCES loi (id)');
        }

        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe CHANGE description_membres description_membres TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE groupe_gn CHANGE agents agents INT DEFAULT 0 NOT NULL, CHANGE bateaux bateaux INT DEFAULT 0 NOT NULL, CHANGE sieges sieges INT DEFAULT 0 NOT NULL, CHANGE initiative initiative INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE log_action CHANGE data data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        // no-op: personnages_connaissances PK change was skipped in up()
        $this->addSql('ALTER TABLE personnages_lignee DROP FOREIGN KEY FK_E0C9F0802B3F22E1');
        $this->addSql('ALTER TABLE personnages_lignee CHANGE lignee_id lignee_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE relecture DROP FOREIGN KEY FK_FC5CF714631F6BDE');
        $this->addSql('ALTER TABLE religion CHANGE secret secret TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE technologie DROP FOREIGN KEY FK_AD813674F7EB2017');
        $this->addSql('ALTER TABLE territoire_has_loi DROP FOREIGN KEY FK_5470401BAB82AB5');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCD0F97A8');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCB011823');
    }
}
