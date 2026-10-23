<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Note complementaire des joueurs sur une conception d heritier (conception.note)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conception ADD note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conception DROP note');
    }
}
