<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412111238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnage ADD scenariste_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage ADD CONSTRAINT FK_6AEA486D1674CEC6 FOREIGN KEY (scenariste_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6AEA486D1674CEC6 ON personnage (scenariste_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personnage DROP FOREIGN KEY FK_6AEA486D1674CEC6');
        $this->addSql('DROP INDEX IDX_6AEA486D1674CEC6 ON personnage');
        $this->addSql('ALTER TABLE personnage DROP scenariste_id');
    }
}
