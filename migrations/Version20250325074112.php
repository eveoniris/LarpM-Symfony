<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250325074112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE espece (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, secret TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE espece_personnage (espece_id INT NOT NULL, personnage_id INT NOT NULL, INDEX IDX_D7C6D24D2D191E7A (espece_id), INDEX IDX_D7C6D24D5E315342 (personnage_id), PRIMARY KEY(espece_id, personnage_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_action (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', date DATETIME NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_5236DF30A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE merveille (id INT AUTO_INCREMENT NOT NULL, territoire_id INT DEFAULT NULL, bonus_id INT DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, description_scenariste LONGTEXT DEFAULT NULL, description_cartographe LONGTEXT DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, date_creatation DATE DEFAULT NULL, date_destruction DATE DEFAULT NULL, INDEX IDX_D70330D0D0F97A8 (territoire_id), INDEX IDX_D70330D069545666 (bonus_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnage_apprentissage (id INT AUTO_INCREMENT NOT NULL, personnage_id INT DEFAULT NULL, enseignant_id INT NOT NULL, date_enseignement DATE DEFAULT NULL, date_usage DATE DEFAULT NULL, INDEX IDX_EA259B515E315342 (personnage_id), INDEX IDX_EA259B51E455FCC0 (enseignant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE espece_personnage ADD CONSTRAINT FK_D7C6D24D2D191E7A FOREIGN KEY (espece_id) REFERENCES espece (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE espece_personnage ADD CONSTRAINT FK_D7C6D24D5E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_action ADD CONSTRAINT FK_5236DF30A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE merveille ADD CONSTRAINT FK_D70330D0D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE merveille ADD CONSTRAINT FK_D70330D069545666 FOREIGN KEY (bonus_id) REFERENCES bonus (id)');
        $this->addSql('ALTER TABLE personnage_apprentissage ADD CONSTRAINT FK_EA259B515E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE personnage_apprentissage ADD CONSTRAINT FK_EA259B51E455FCC0 FOREIGN KEY (enseignant_id) REFERENCES personnage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE espece_personnage DROP FOREIGN KEY FK_D7C6D24D2D191E7A');
        $this->addSql('ALTER TABLE espece_personnage DROP FOREIGN KEY FK_D7C6D24D5E315342');
        $this->addSql('ALTER TABLE log_action DROP FOREIGN KEY FK_5236DF30A76ED395');
        $this->addSql('ALTER TABLE merveille DROP FOREIGN KEY FK_D70330D0D0F97A8');
        $this->addSql('ALTER TABLE merveille DROP FOREIGN KEY FK_D70330D069545666');
        $this->addSql('ALTER TABLE personnage_apprentissage DROP FOREIGN KEY FK_EA259B515E315342');
        $this->addSql('ALTER TABLE personnage_apprentissage DROP FOREIGN KEY FK_EA259B51E455FCC0');
        $this->addSql('DROP TABLE espece');
        $this->addSql('DROP TABLE espece_personnage');
        $this->addSql('DROP TABLE log_action');
        $this->addSql('DROP TABLE merveille');
        $this->addSql('DROP TABLE personnage_apprentissage');
    }
}
