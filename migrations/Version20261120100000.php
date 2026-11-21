<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gn.date_verrouillage : jalon public de verrouillage des modifications (chronologie).
 */
final class Version20261120100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gn.date_verrouillage (date de verrouillage affichée dans la chronologie)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD date_verrouillage DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn DROP date_verrouillage');
    }
}
