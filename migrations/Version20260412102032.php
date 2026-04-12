<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412102032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE territoire ADD sanctuaire_religion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F5432DE21F FOREIGN KEY (sanctuaire_religion_id) REFERENCES religion (id)');
        $this->addSql('CREATE INDEX IDX_B8655F5432DE21F ON territoire (sanctuaire_religion_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F5432DE21F');
        $this->addSql('DROP INDEX IDX_B8655F5432DE21F ON territoire');
        $this->addSql('ALTER TABLE territoire DROP sanctuaire_religion_id');
    }
}
