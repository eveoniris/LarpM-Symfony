<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'api_client : applications tierces de l\'API (clé d\'API, compte de service)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE api_client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, client_id VARCHAR(40) NOT NULL, secret_hash VARCHAR(64) NOT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, user_id INT UNSIGNED NOT NULL, UNIQUE INDEX UNIQ_41B343D55E237E06 (name), UNIQUE INDEX UNIQ_41B343D519EB6921 (client_id), INDEX IDX_41B343D5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_client');
    }
}
