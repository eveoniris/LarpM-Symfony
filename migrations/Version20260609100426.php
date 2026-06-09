<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609100426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message CHANGE archived archived TINYINT NOT NULL');
        $this->addSql('ALTER TABLE participant ADD carte_alchi_dans_enveloppe TINYINT DEFAULT NULL, ADD enveloppe_precedent_gn TINYINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message CHANGE archived archived TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE participant DROP carte_alchi_dans_enveloppe, DROP enveloppe_precedent_gn');
    }
}
