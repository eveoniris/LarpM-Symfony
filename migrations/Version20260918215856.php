<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918215856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les tables cosmogonie et autre_monde (menu Univers)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE autre_monde (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, description_scenariste LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE cosmogonie (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, description_scenariste LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE autre_monde');
        $this->addSql('DROP TABLE cosmogonie');
    }
}
