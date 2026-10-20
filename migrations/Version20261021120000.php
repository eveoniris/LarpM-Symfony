<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'connaissance : champ texte transmission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connaissance ADD transmission LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE connaissance DROP transmission');
    }
}
