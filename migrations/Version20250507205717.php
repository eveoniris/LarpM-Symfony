<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250507205717 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group DROP discord
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE groupe_gn DROP diplomate_id
        SQL,
        );
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            <<<'SQL'
            ALTER TABLE secondary_group ADD discord VARCHAR(255) DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE groupe_gn ADD diplomate_id INT DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C8EAED05C FOREIGN KEY (diplomate_id) REFERENCES personnage (id)
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            CREATE INDEX IDX_413F11C8EAED05C ON groupe_gn (diplomate_id)
        SQL,
        );
    }
}
