<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * experience_gain.deleted_at : suppression logique d'un gain d'XP retiré par un admin.
 */
final class Version20261119140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'experience_gain.deleted_at (retrait d\'XP avec suppression logique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE experience_gain ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE experience_gain DROP deleted_at');
    }
}
