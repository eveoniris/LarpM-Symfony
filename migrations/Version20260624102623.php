<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne trigger_tag sur personnage_langues : trace, pour une langue de source
 * LITTERATURE, le tag du trigger consommé (LANGUE COURANTE / LANGUE ANCIENNE).
 */
final class Version20260624102623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute personnage_langues.trigger_tag (tag du trigger consommé pour une langue LITTERATURE)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_langues ADD trigger_tag VARCHAR(45) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_langues DROP trigger_tag');
    }
}
