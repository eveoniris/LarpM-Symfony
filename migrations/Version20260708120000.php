<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute personnages_religions.verrouille : permet à un admin de verrouiller une religion
 * d'un personnage pour qu'elle ne soit pas effacée par la cascade Fanatique/Sans ni par un
 * retrait manuel orga/scénariste.
 */
final class Version20260708120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute personnages_religions.verrouille (verrou de protection contre le retrait)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnages_religions ADD verrouille TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnages_religions DROP verrouille');
    }
}
