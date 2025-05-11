<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250510143417 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group DROP FOREIGN KEY FK_717A91A31674CEC6
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            DROP INDEX IDX_717A91A31674CEC6 ON secondary_group
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group DROP scenariste_id
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
            ALTER TABLE secondary_group ADD scenariste_id INT UNSIGNED DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group ADD CONSTRAINT FK_717A91A31674CEC6 FOREIGN KEY (scenariste_id) REFERENCES user (id)
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_717A91A31674CEC6 ON secondary_group (scenariste_id)
        SQL,
        );

    }
}
