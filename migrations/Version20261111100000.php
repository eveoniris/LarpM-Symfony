<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'conception.nom_propose : nom envisagé par les joueurs pour préremplir la création de l\'héritier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conception ADD nom_propose VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conception DROP nom_propose');
    }
}
