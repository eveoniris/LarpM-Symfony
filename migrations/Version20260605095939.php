<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605095939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sépare lu (vu) et archived (archivé) dans Message';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message ADD archived TINYINT NOT NULL DEFAULT 0');
        // Les messages déjà marqués lu=1 étaient "archivés" dans l'ancien modèle
        $this->addSql('UPDATE message SET archived = 1 WHERE lu = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP archived');
    }
}
