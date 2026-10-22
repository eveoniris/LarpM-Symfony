<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Places des groupes : la limite manuelle passe de groupe.places_max a groupe_gn.places_max (par session, null = calcul automatique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE groupe_gn ADD places_max INT DEFAULT NULL');

        // Les limites existantes ne valent que pour le dernier GN : les sessions suivantes repartent du calcul automatique
        $this->addSql('UPDATE groupe_gn gg INNER JOIN `groupe` g ON g.id = gg.groupe_id SET gg.places_max = g.places_max WHERE g.places_max IS NOT NULL AND gg.gn_id = (SELECT MAX(id) FROM gn)');

        $this->addSql('ALTER TABLE `groupe` DROP places_max');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `groupe` ADD places_max INT DEFAULT NULL');
        $this->addSql('UPDATE `groupe` g INNER JOIN groupe_gn gg ON gg.groupe_id = g.id SET g.places_max = gg.places_max WHERE gg.places_max IS NOT NULL AND gg.gn_id = (SELECT MAX(id) FROM gn)');
        $this->addSql('ALTER TABLE groupe_gn DROP places_max');
    }
}
