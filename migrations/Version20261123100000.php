<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Verrouillage à deux niveaux des groupes : colonne lock_strict (souple = lock seul, strict = lock + lock_strict).
 */
final class Version20261123100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Groupe : colonne lock_strict (verrouillage strict)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `groupe` ADD lock_strict TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `groupe` DROP lock_strict');
    }
}
