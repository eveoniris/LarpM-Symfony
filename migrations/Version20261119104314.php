<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * message.auteur devient nullable : un message sans auteur est un message système (« Équipe de gestion »).
 */
final class Version20261119104314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'message.auteur nullable (message système)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message CHANGE auteur auteur INT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Les messages système n'ont pas d'auteur : impossible de remettre NOT NULL sans les supprimer.
        $this->addSql('DELETE FROM message WHERE auteur IS NULL');
        $this->addSql('ALTER TABLE message CHANGE auteur auteur INT UNSIGNED NOT NULL');
    }
}
