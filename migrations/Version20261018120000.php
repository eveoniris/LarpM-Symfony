<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.ui_theme (choix de l\'interface : bêta du nouveau thème)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD ui_theme VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP ui_theme');
    }
}
