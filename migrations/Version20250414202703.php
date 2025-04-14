<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414202703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence ADD personnage_apprentissage_id INT DEFAULT NULL, ADD secret TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE competence ADD CONSTRAINT FK_94D4687FD28E06CB FOREIGN KEY (personnage_apprentissage_id) REFERENCES personnage_apprentissage (id)');
        $this->addSql('CREATE INDEX IDX_94D4687FD28E06CB ON competence (personnage_apprentissage_id)');
        $this->addSql('ALTER TABLE personnage_apprentissage ADD created_at DATE DEFAULT NULL, CHANGE date_enseignement date_enseignement INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence DROP FOREIGN KEY FK_94D4687FD28E06CB');
        $this->addSql('DROP INDEX IDX_94D4687FD28E06CB ON competence');
        $this->addSql('ALTER TABLE competence DROP personnage_apprentissage_id, DROP secret');
        $this->addSql('ALTER TABLE personnage_apprentissage DROP created_at, CHANGE date_enseignement date_enseignement DATE DEFAULT NULL');
    }
}
