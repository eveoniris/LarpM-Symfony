<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108102451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'api_client_request : demandes de création d\'applications tierces';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_client_request (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, purpose LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, decided_at DATETIME DEFAULT NULL, decision_note LONGTEXT DEFAULT NULL, requester_id INT UNSIGNED NOT NULL, gn_id INT DEFAULT NULL, decided_by_id INT UNSIGNED DEFAULT NULL, INDEX IDX_8D0C695CED442CF4 (requester_id), INDEX IDX_8D0C695CAFC9C052 (gn_id), INDEX IDX_8D0C695CE26B496B (decided_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE api_client_request ADD CONSTRAINT FK_8D0C695CED442CF4 FOREIGN KEY (requester_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE api_client_request ADD CONSTRAINT FK_8D0C695CAFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE api_client_request ADD CONSTRAINT FK_8D0C695CE26B496B FOREIGN KEY (decided_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client_request DROP FOREIGN KEY FK_8D0C695CED442CF4');
        $this->addSql('ALTER TABLE api_client_request DROP FOREIGN KEY FK_8D0C695CAFC9C052');
        $this->addSql('ALTER TABLE api_client_request DROP FOREIGN KEY FK_8D0C695CE26B496B');
        $this->addSql('DROP TABLE api_client_request');
    }
}
