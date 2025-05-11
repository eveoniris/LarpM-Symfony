<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250510113837 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group DROP private
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE membre DROP private
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
            ALTER TABLE membre ADD private TINYINT(1) DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group ADD private TINYINT(1) DEFAULT NULL
        SQL,
        );
    }
}
