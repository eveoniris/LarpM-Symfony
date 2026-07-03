<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne `revenu` à `construction` (revenu en pièces d'argent apporté au territoire)
 * et charge le barème initial : Comptoir (6) = 10, Port (10) = 5, Foyer d'orfèvre (23) = 10.
 */
final class Version20260703101006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute construction.revenu (revenu en PA) et charge le barème initial des constructions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE construction ADD revenu INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE construction SET revenu = 10 WHERE id = 6'); // Comptoir commercial
        $this->addSql('UPDATE construction SET revenu = 5 WHERE id = 10'); // Port
        $this->addSql('UPDATE construction SET revenu = 10 WHERE id = 23'); // Foyer d'orfèvre
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE construction DROP revenu');
    }
}
