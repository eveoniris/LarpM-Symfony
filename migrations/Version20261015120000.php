<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.discord_pseudo (pseudo Discord facultatif)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD discord_pseudo VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP discord_pseudo');
    }
}
