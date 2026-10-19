<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'personnage_bonus : source et territoire (bonus d\'origine figé à l\'acquisition), unicité par origine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_bonus ADD source VARCHAR(36) DEFAULT NULL, ADD territoire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage_bonus ADD CONSTRAINT FK_35CB7340D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('CREATE INDEX fk_personnage_bonus_territoire_idx ON personnage_bonus (territoire_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_personnage_bonus_origine ON personnage_bonus (personnage_id, bonus_id, territoire_id)');
        $this->addSql("UPDATE personnage_bonus SET source = 'ESPECE' WHERE espece_id IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_personnage_bonus_origine ON personnage_bonus');
        $this->addSql('ALTER TABLE personnage_bonus DROP FOREIGN KEY FK_35CB7340D0F97A8');
        $this->addSql('DROP INDEX fk_personnage_bonus_territoire_idx ON personnage_bonus');
        $this->addSql('ALTER TABLE personnage_bonus DROP source, DROP territoire_id');
    }
}
