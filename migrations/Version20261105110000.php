<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adresse IP et user agent de la requete dans log_action';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_action ADD ip VARCHAR(45) DEFAULT NULL, ADD user_agent VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_action DROP ip, DROP user_agent');
    }
}
