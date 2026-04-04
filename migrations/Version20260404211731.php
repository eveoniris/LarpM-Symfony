<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404211731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE territoire ADD allowed_in_character_creation TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('UPDATE territoire SET allowed_in_character_creation = 0 WHERE id IN (454, 453, 452)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
         $this->addSql('ALTER TABLE territoire DROP allowed_in_character_creation');
    }
}
