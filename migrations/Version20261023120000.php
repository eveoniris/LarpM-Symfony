<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'langue : champ texte condition_apprentissage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE langue ADD condition_apprentissage LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE langue DROP condition_apprentissage');
    }
}
