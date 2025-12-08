<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205215403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE secondary_group DROP FOREIGN KEY FK_717A91A31F55203D');
        $this->addSql('DROP INDEX fk_secondary_group_topic1_idx ON secondary_group');
        $this->addSql('ALTER TABLE secondary_group DROP topic_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE secondary_group ADD topic_id INT NOT NULL');
        $this->addSql('ALTER TABLE secondary_group ADD CONSTRAINT FK_717A91A31F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('CREATE INDEX fk_secondary_group_topic1_idx ON secondary_group (topic_id)');
    }
}
