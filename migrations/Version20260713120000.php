<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne `user.passwordResetTtl` : durée de validité (en secondes) à appliquer au
 * token de réinitialisation courant. Null = TTL public par défaut (passwordTokenTTL). Renseignée
 * lorsqu'un super-admin génère un lien de support avec un TTL dédié (passwordTokenTTLSupport).
 */
final class Version20260713120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.passwordResetTtl (TTL dédié aux liens de réinitialisation générés par le support).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD passwordResetTtl INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP passwordResetTtl');
    }
}
