<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407060810 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP email_contact');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD email_contact VARCHAR(255) DEFAULT NULL'
        );
    }
}
