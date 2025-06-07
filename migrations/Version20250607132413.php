<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250607132413 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE territoire ADD topic_id INT NOT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE message CHANGE title title VARCHAR(45) DEFAULT NULL
            SQL,
        );
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE territoire DROP topic_id
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE message CHANGE title title VARCHAR(255) DEFAULT NULL
        SQL,
        );
    }
}
