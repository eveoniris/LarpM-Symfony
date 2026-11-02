<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.discord_id (identifiant Discord verifie par OAuth, unique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD discord_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64943349DE ON user (discord_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D64943349DE ON user');
        $this->addSql('ALTER TABLE user DROP discord_id');
    }
}
