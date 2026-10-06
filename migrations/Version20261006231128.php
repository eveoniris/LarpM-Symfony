<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006231128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('espece_bonus')) {
            $this->addSql('CREATE TABLE espece_bonus (id INT AUTO_INCREMENT NOT NULL, creation_date DATETIME NOT NULL, status VARCHAR(32) NOT NULL, espece_id INT NOT NULL, bonus_id INT NOT NULL, INDEX fk_espece_bonus_bonus_idx (bonus_id), INDEX fk_espece_bonus_espece_idx (espece_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
            $this->addSql('ALTER TABLE espece_bonus ADD CONSTRAINT FK_6FD15EA92D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id)');
            $this->addSql('ALTER TABLE espece_bonus ADD CONSTRAINT FK_6FD15EA969545666 FOREIGN KEY (bonus_id) REFERENCES bonus (id)');
        }

        if (!$schema->getTable('espece')->hasColumn('energie_vitale')) {
            $this->addSql('ALTER TABLE espece ADD energie_vitale INT DEFAULT NULL');
        }

        if (!$schema->getTable('personnage_bonus')->hasColumn('espece_id')) {
            $this->addSql('ALTER TABLE personnage_bonus ADD espece_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE personnage_bonus ADD CONSTRAINT FK_35CB73402D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id)');
            $this->addSql('CREATE INDEX fk_personnage_bonus_espece_idx ON personnage_bonus (espece_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('espece_bonus')) {
            $this->addSql('ALTER TABLE espece_bonus DROP FOREIGN KEY FK_6FD15EA92D191E7A');
            $this->addSql('ALTER TABLE espece_bonus DROP FOREIGN KEY FK_6FD15EA969545666');
            $this->addSql('DROP TABLE espece_bonus');
        }

        if ($schema->getTable('espece')->hasColumn('energie_vitale')) {
            $this->addSql('ALTER TABLE espece DROP energie_vitale');
        }

        if ($schema->getTable('personnage_bonus')->hasColumn('espece_id')) {
            $this->addSql('ALTER TABLE personnage_bonus DROP FOREIGN KEY FK_35CB73402D191E7A');
            $this->addSql('DROP INDEX fk_personnage_bonus_espece_idx ON personnage_bonus');
            $this->addSql('ALTER TABLE personnage_bonus DROP espece_id');
        }
    }
}
