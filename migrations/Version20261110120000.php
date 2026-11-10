<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'priere.sphere_id obligatoire (toutes les prières ont une sphère)';
    }

    public function up(Schema $schema): void
    {
        $sansSphere = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM priere WHERE sphere_id IS NULL');
        $this->abortIf(0 < $sansSphere, \sprintf('%d prière(s) sans sphère : corriger les données avant cette migration.', $sansSphere));

        $this->addSql('ALTER TABLE priere CHANGE sphere_id sphere_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE priere CHANGE sphere_id sphere_id INT DEFAULT NULL');
    }
}
