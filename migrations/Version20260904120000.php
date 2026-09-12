<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Personnage de substitution : un personnage joué dans les instances situées hors
 * du temps et du lieu de l'événement, activable opus par opus.
 */
final class Version20260904120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le personnage de substitution sur la participation et son option sur le GN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD substitution_active TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE gn ADD substitution_description LONGTEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE participant ADD personnage_substitution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B1176631E29 FOREIGN KEY (personnage_substitution_id) REFERENCES personnage (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D79F6B1176631E29 ON participant (personnage_substitution_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B1176631E29');
        $this->addSql('DROP INDEX IDX_D79F6B1176631E29 ON participant');
        $this->addSql('ALTER TABLE participant DROP personnage_substitution_id');

        $this->addSql('ALTER TABLE gn DROP substitution_active');
        $this->addSql('ALTER TABLE gn DROP substitution_description');
    }
}
