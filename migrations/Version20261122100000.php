<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gn_timeline_step : date prévue facultative (le verrouillage suit la date du GN), années et âge de mort du vieillissement.
 */
final class Version20261122100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gn_timeline_step : date_prevue nullable, annees, age_mort';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn_timeline_step CHANGE date_prevue date_prevue DATETIME DEFAULT NULL, ADD annees INT DEFAULT NULL, ADD age_mort INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE gn_timeline_step SET date_prevue = NOW() WHERE date_prevue IS NULL');
        $this->addSql('ALTER TABLE gn_timeline_step CHANGE date_prevue date_prevue DATETIME NOT NULL, DROP annees, DROP age_mort');
    }
}
