<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407110432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'territoire_quete: convert MyISAM to InnoDB and add missing FK constraints';
    }

    private function fkExists(string $table, string $constraintName): bool
    {
        return (bool) $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraintName]
        );
    }

    public function up(Schema $schema): void
    {
        // territoire_quete used MyISAM (no FK support) — convert to InnoDB first
        $this->addSql('ALTER TABLE territoire_quete ENGINE=InnoDB');

        if (!$this->fkExists('territoire_quete', 'FK_63718DCD0F97A8')) {
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        }
        if (!$this->fkExists('territoire_quete', 'FK_63718DCB011823')) {
            $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCB011823 FOREIGN KEY (territoire_cible_id) REFERENCES territoire (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCD0F97A8');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCB011823');
        $this->addSql('ALTER TABLE territoire_quete ENGINE=MyISAM');
    }
}
