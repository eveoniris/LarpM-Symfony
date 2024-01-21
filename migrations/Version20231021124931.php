<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231021124931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Init';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE user ADD is_enabled TINYINT');
        $this->addSql('ALTER TABLE user ADD pwd VARCHAR(255)');
        $this->addSql('UPDATE user SET is_enabled = isEnabled WHERE is_enabled <> isEnabled');
        $this->addSql('ALTER TABLE groupe_gn ADD suzerain_id INT(11) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP roles');
        $this->addSql('ALTER TABLE user DROP pwd');
        $this->addSql('ALTER TABLE user DROP COLUMN is_enabled');
        $this->addSql('ALTER TABLE groupe_gn DROP suzerain_id');
    }
}
