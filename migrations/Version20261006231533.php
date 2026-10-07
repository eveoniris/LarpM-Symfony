<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006231533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'data: set energie_vitale=2 for Ombrelin species';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE espece SET energie_vitale = 2 WHERE UPPER(nom) = 'OMBRELIN'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE espece SET energie_vitale = NULL WHERE UPPER(nom) = 'OMBRELIN'");
    }
}
