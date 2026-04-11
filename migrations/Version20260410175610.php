<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410175610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add territoire_frontalier_culturel ManyToMany table for cultural border fiefs';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE territoire_frontalier_culturel (territoire_id INT NOT NULL, territoire_frontalier_id INT NOT NULL, INDEX IDX_D6BB3C63D0F97A8 (territoire_id), INDEX IDX_D6BB3C6354A2AE92 (territoire_frontalier_id), PRIMARY KEY (territoire_id, territoire_frontalier_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE territoire_frontalier_culturel ADD CONSTRAINT FK_D6BB3C63D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_frontalier_culturel ADD CONSTRAINT FK_D6BB3C6354A2AE92 FOREIGN KEY (territoire_frontalier_id) REFERENCES territoire (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE territoire_frontalier_culturel DROP FOREIGN KEY FK_D6BB3C63D0F97A8');
        $this->addSql('ALTER TABLE territoire_frontalier_culturel DROP FOREIGN KEY FK_D6BB3C6354A2AE92');
        $this->addSql('DROP TABLE territoire_frontalier_culturel');
    }
}
