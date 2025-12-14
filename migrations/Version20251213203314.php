<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213203314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gn DROP FOREIGN KEY FK_C16FA3C01F55203D');
        $this->addSql('ALTER TABLE religion DROP FOREIGN KEY FK_1055F4F91F55203D');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D1F55203D');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D4B89032C');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE post_view DROP FOREIGN KEY FK_37A8CC854B89032C');
        $this->addSql('ALTER TABLE post_view DROP FOREIGN KEY FK_37A8CC85A76ED395');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1B1F55203D');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1BA76ED395');
        $this->addSql('ALTER TABLE watching_user DROP FOREIGN KEY FK_FFDC43024B89032C');
        $this->addSql('ALTER TABLE watching_user DROP FOREIGN KEY FK_FFDC4302A76ED395');
        $this->addSql('DROP TABLE item_bak');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE post_view');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE watching_user');
        $this->addSql('DROP TABLE wt_heroisme_ad');
        $this->addSql('DROP TABLE wt_litterature_top');
        $this->addSql('DROP INDEX fk_gn_topic1_idx ON gn');
        $this->addSql('ALTER TABLE gn DROP topic_id');
        $this->addSql('DROP INDEX fk_groupe_topic1_idx ON groupe');
        $this->addSql('ALTER TABLE groupe DROP topic_id');
        $this->addSql('DROP INDEX fk_religion_topic1_idx ON religion');
        $this->addSql('ALTER TABLE religion DROP topic_id');
        $this->addSql('ALTER TABLE territoire DROP topic_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_bak (id INT UNSIGNED AUTO_INCREMENT NOT NULL, quality_id INT NOT NULL, statut_id INT DEFAULT NULL, label VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, numero INT NOT NULL, identification INT NOT NULL, special LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, couleur VARCHAR(45) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, date_creation DATETIME NOT NULL, date_update DATETIME NOT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, objet_id INT NOT NULL, quantite INT DEFAULT 1 NOT NULL, INDEX fk_item_objet1_idx (objet_id), INDEX fk_item_qualite1_idx (quality_id), INDEX fk_item_statut1_idx (statut_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, topic_id INT DEFAULT NULL, user_id INT UNSIGNED NOT NULL, post_id INT DEFAULT NULL, title VARCHAR(450) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, text LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, creation_date DATETIME DEFAULT NULL, update_date DATETIME DEFAULT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_post_post1_idx (post_id), INDEX fk_post_topic1_idx (topic_id), INDEX fk_post_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post_view (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT UNSIGNED NOT NULL, date DATETIME NOT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_post_view_post1_idx (post_id), INDEX fk_post_view_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE topic (id INT AUTO_INCREMENT NOT NULL, topic_id INT DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, title VARCHAR(450) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, creation_date DATETIME DEFAULT NULL, update_date DATETIME DEFAULT NULL, `right` VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, object_id INT DEFAULT NULL, `key` VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_topic_topic1_idx (topic_id), INDEX fk_topic_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE watching_user (post_id INT NOT NULL, user_id INT UNSIGNED NOT NULL, INDEX IDX_FFDC43024B89032C (post_id), INDEX IDX_FFDC4302A76ED395 (user_id), PRIMARY KEY(post_id, user_id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wt_heroisme_ad (id INT AUTO_INCREMENT NOT NULL, gn_id INT NOT NULL, personnage_id INT NOT NULL, competence_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wt_litterature_top (id INT AUTO_INCREMENT NOT NULL, gn_id INT NOT NULL, personnage_id INT NOT NULL, Compétence INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_view ADD CONSTRAINT FK_37A8CC854B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE post_view ADD CONSTRAINT FK_37A8CC85A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1B1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE watching_user ADD CONSTRAINT FK_FFDC43024B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE watching_user ADD CONSTRAINT FK_FFDC4302A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE gn ADD topic_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gn ADD CONSTRAINT FK_C16FA3C01F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('CREATE INDEX fk_gn_topic1_idx ON gn (topic_id)');
        $this->addSql('ALTER TABLE groupe ADD topic_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX fk_groupe_topic1_idx ON groupe (topic_id)');
        $this->addSql('ALTER TABLE religion ADD topic_id INT NOT NULL');
        $this->addSql('ALTER TABLE religion ADD CONSTRAINT FK_1055F4F91F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('CREATE INDEX fk_religion_topic1_idx ON religion (topic_id)');
        $this->addSql('ALTER TABLE territoire ADD topic_id INT DEFAULT NULL');
    }
}
