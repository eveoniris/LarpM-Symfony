<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gn_timeline_step : type d'étape, exécution automatique, statut et compte rendu d'exécution.
 */
final class Version20261121100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gn_timeline_step : type, auto, statut, executed_at, resultat, xp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE gn_timeline_step ADD type VARCHAR(30) DEFAULT 'info' NOT NULL, ADD auto TINYINT NOT NULL DEFAULT 0, ADD statut VARCHAR(20) DEFAULT 'a_faire' NOT NULL, ADD executed_at DATETIME DEFAULT NULL, ADD resultat LONGTEXT DEFAULT NULL, ADD xp INT DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn_timeline_step DROP type, DROP auto, DROP statut, DROP executed_at, DROP resultat, DROP xp');
    }
}
