<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260612141137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Déplace le personnage de relève de User vers Participant (prochain GN actif)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participant ADD personnage_releve_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11660B42E6 FOREIGN KEY (personnage_releve_id) REFERENCES personnage (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D79F6B11660B42E6 ON participant (personnage_releve_id)');
        // Migration des données : copie vers la participation au prochain GN actif
        $this->addSql('
            UPDATE participant p
            INNER JOIN user u ON p.user_id = u.id
            INNER JOIN (
                SELECT id FROM gn WHERE actif = 1 ORDER BY date_debut DESC LIMIT 1
            ) next_gn ON p.gn_id = next_gn.id
            SET p.personnage_releve_id = u.personnage_secondaire_id
            WHERE u.personnage_secondaire_id IS NOT NULL
        ');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `FK_8D93D649E6917FB3`');
        $this->addSql('DROP INDEX fk_user_personnage_secondaire1_idx ON user');
        $this->addSql('ALTER TABLE user DROP personnage_secondaire_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11660B42E6');
        $this->addSql('DROP INDEX IDX_D79F6B11660B42E6 ON participant');
        $this->addSql('ALTER TABLE participant DROP personnage_releve_id');
        $this->addSql('ALTER TABLE user ADD personnage_secondaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT `FK_8D93D649E6917FB3` FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX fk_user_personnage_secondaire1_idx ON user (personnage_secondaire_id)');
    }
}
