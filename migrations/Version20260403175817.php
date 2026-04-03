<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403175817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chronologie_generee flag to inter_jeu to prevent duplicate chronology generation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inter_jeu ADD chronologie_generee TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inter_jeu DROP chronologie_generee');
    }
}
