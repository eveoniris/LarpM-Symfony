<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250215141604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bonus (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, valeur INT DEFAULT NULL, periode VARCHAR(32) DEFAULT NULL, application VARCHAR(32) DEFAULT NULL, competence_id INT DEFAULT NULL, json_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', discr VARCHAR(255) NOT NULL, INDEX fk_personnage_groupe1_idx (competence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bonus');
    }
}
