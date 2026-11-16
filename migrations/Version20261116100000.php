<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'login_history : historique des connexions (date, IP, user agent, empreinte serveur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE login_history (id INT UNSIGNED AUTO_INCREMENT NOT NULL, identifier VARCHAR(180) DEFAULT NULL, date DATETIME NOT NULL, success TINYINT NOT NULL, method VARCHAR(20) NOT NULL, failure_reason VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, ip_anonymized TINYINT DEFAULT 0 NOT NULL, user_agent VARCHAR(512) DEFAULT NULL, fingerprint VARCHAR(64) DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, INDEX IDX_37976E36A76ED395 (user_id), INDEX idx_login_history_user_date (user_id, date), INDEX idx_login_history_ip_date (ip, date), INDEX idx_login_history_date (date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE login_history ADD CONSTRAINT FK_37976E36A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE login_history DROP FOREIGN KEY FK_37976E36A76ED395');
        $this->addSql('DROP TABLE login_history');
    }
}
