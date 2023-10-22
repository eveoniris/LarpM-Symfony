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


    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP roles');
    }
}
