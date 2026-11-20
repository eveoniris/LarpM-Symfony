<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gn.date_temps_jeu_debut / date_temps_jeu_fin : période réelle pendant laquelle le temps de jeu s'écoule avant le GN.
 */
final class Version20261120120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gn.date_temps_jeu_debut et date_temps_jeu_fin (écoulement du temps de jeu)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD date_temps_jeu_debut DATETIME DEFAULT NULL, ADD date_temps_jeu_fin DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn DROP date_temps_jeu_debut, DROP date_temps_jeu_fin');
    }
}
