<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table des adresses IP bloquées par un administrateur.
 */
final class Version20261124100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table ip_bloquee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE ip_bloquee (id INT UNSIGNED AUTO_INCREMENT NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, cidr VARCHAR(49) NOT NULL, motif VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, INDEX IDX_IP_BLOQUEE_CREATED_BY (created_by_id), INDEX idx_ip_bloquee_expires (expires_at), UNIQUE INDEX uniq_ip_bloquee_cidr (cidr), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
        );
        $this->addSql('ALTER TABLE ip_bloquee ADD CONSTRAINT FK_IP_BLOQUEE_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ip_bloquee');
    }
}
