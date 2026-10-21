<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Places des groupes : gn.places_par_groupe, groupe.classe_open renomme places_max (null = calcul automatique), billet.compte_dans_places (aide de camp)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD places_par_groupe INT DEFAULT 12 NOT NULL');

        $this->addSql('ALTER TABLE groupe RENAME COLUMN classe_open TO places_max');
        // 0 signifiait "non renseigne" : null = calcul automatique
        $this->addSql('UPDATE groupe SET places_max = NULL WHERE places_max = 0');

        $this->addSql('ALTER TABLE billet ADD compte_dans_places TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql("UPDATE billet SET compte_dans_places = 0 WHERE LOWER(label) LIKE '%aide de camp%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billet DROP compte_dans_places');

        $this->addSql('UPDATE groupe SET places_max = 0 WHERE places_max IS NULL');
        $this->addSql('ALTER TABLE groupe RENAME COLUMN places_max TO classe_open');

        $this->addSql('ALTER TABLE gn DROP places_par_groupe');
    }
}
