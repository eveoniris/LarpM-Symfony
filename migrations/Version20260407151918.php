<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407151918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert personnages_chronologie and personnages_lignee to utf8mb4 (tables were latin1 default with utf8mb3 columns)';
    }

    public function up(Schema $schema): void
    {
        // Both tables have DEFAULT CHARSET=latin1 with explicit utf8mb3 columns.
        // CONVERT TO CHARACTER SET utf8mb4 converts both the table default and all column data
        // safely (utf8mb4 is a superset of utf8mb3 so all existing data is preserved).
        $this->addSql('ALTER TABLE personnages_chronologie CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE personnages_chronologie CHANGE discr discr VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE personnages_lignee CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE personnages_lignee CHANGE discr discr VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnages_chronologie CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
        $this->addSql('ALTER TABLE personnages_lignee CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
    }
}
