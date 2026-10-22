<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'personnage_trigger : origine du trigger (achat de compétence)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_trigger ADD source VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_trigger DROP source');
    }
}
