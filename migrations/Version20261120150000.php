<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gn_timeline_step : étapes de la chronologie d'un GN avec visibilité (public, secret, technique).
 */
final class Version20261120150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table gn_timeline_step (étapes de chronologie avec visibilité)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE gn_timeline_step (id INT UNSIGNED AUTO_INCREMENT NOT NULL, gn_id INT NOT NULL, libelle VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, date_prevue DATETIME NOT NULL, visibilite VARCHAR(20) NOT NULL, INDEX IDX_BB145191AFC9C052 (gn_id), INDEX idx_gn_timeline_step_date (date_prevue), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE gn_timeline_step ADD CONSTRAINT FK_GN_TIMELINE_STEP_GN FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE gn_timeline_step');
    }
}
