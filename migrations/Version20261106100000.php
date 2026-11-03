<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'secondary_group.discord_ignore : groupes inactifs ignores par le provisionnement du bot Discord';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE secondary_group ADD discord_ignore TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE secondary_group DROP discord_ignore');
    }
}
