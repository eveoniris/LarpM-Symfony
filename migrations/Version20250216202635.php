<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250216202635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE secondary_group DROP FOREIGN KEY FK_717A91A31F55203D');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F541F55203D');
        $this->addSql('ALTER TABLE religion DROP FOREIGN KEY FK_1055F4F91F55203D');
        $this->addSql('ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C211F55203D');
        $this->addSql('ALTER TABLE gn DROP FOREIGN KEY FK_C16FA3C01F55203D');
        $this->addSql(
            'CREATE TABLE origine_bonus (id INT AUTO_INCREMENT NOT NULL, bonus_id INT NOT NULL, territoire_id INT NOT NULL, creation_date DATETIME NOT NULL, status VARCHAR(32) not null, INDEX IDX_BE69354723BFF59E (base_bonus_id), INDEX IDX_BE693547D0F97A8 (territoire_id), PRIMARY KEY(base_bonus_id, territoire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE groupe_gn_ordre (id INT AUTO_INCREMENT NOT NULL, groupe_gn_id INT DEFAULT NULL, cible_id INT DEFAULT NULL, ordre VARCHAR(255) NOT NULL, extra VARCHAR(255) NOT NULL, discr VARCHAR(255) NOT NULL, INDEX IDX_BDA495F5FA640E02 (groupe_gn_id), INDEX IDX_BDA495F5A96E5E09 (cible_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE qualite (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(45) NOT NULL, numero INT DEFAULT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE qualite_valeur (id INT AUTO_INCREMENT NOT NULL, monnaie_id INT NOT NULL, qualite_id INT NOT NULL, nombre INT NOT NULL, discr VARCHAR(255) NOT NULL, INDEX fk_qualite_valeur_qualite1_idx (qualite_id), INDEX fk_qualite_valeur_monnaie1_idx (monnaie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE69354723BFF59E FOREIGN KEY (bonus_id) REFERENCES bonus (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE693547D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE groupe_gn_ordre ADD CONSTRAINT FK_BDA495F5FA640E02 FOREIGN KEY (groupe_gn_id) REFERENCES groupe_gn (id)'
        );
        $this->addSql(
            'ALTER TABLE groupe_gn_ordre ADD CONSTRAINT FK_BDA495F5A96E5E09 FOREIGN KEY (cible_id) REFERENCES territoire (id)'
        );
        $this->addSql(
            'ALTER TABLE qualite_valeur ADD CONSTRAINT FK_8F9426EBA6338570 FOREIGN KEY (qualite_id) REFERENCES qualite (id)'
        );
        $this->addSql(
            'ALTER TABLE qualite_valeur ADD CONSTRAINT FK_8F9426EB98D3FE22 FOREIGN KEY (monnaie_id) REFERENCES monnaie (id)'
        );
        $this->addSql('ALTER TABLE post_view DROP FOREIGN KEY FK_37A8CC854B89032C');
        $this->addSql('ALTER TABLE post_view DROP FOREIGN KEY FK_37A8CC85A76ED395');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1B1F55203D');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1BA76ED395');
        $this->addSql('ALTER TABLE watching_user DROP FOREIGN KEY FK_FFDC43024B89032C');
        $this->addSql('ALTER TABLE watching_user DROP FOREIGN KEY FK_FFDC4302A76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D4B89032C');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D1F55203D');
        $this->addSql('DROP TABLE wt_heroisme_ad');
        $this->addSql('DROP TABLE wt_litterature_top');
        $this->addSql('DROP TABLE post_view');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE watching_user');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE item_bak');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE wt_heroisme_ad (id INT AUTO_INCREMENT NOT NULL, gn_id INT NOT NULL, personnage_id INT NOT NULL, competence_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_general_ci` ENGINE = MyISAM COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE wt_litterature_top (id INT AUTO_INCREMENT NOT NULL, gn_id INT NOT NULL, personnage_id INT NOT NULL, Compétence INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_general_ci` ENGINE = MyISAM COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE post_view (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT UNSIGNED NOT NULL, date DATETIME NOT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_post_view_post1_idx (post_id), INDEX fk_post_view_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE topic (id INT AUTO_INCREMENT NOT NULL, topic_id INT DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, title VARCHAR(450) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, creation_date DATETIME DEFAULT NULL, update_date DATETIME DEFAULT NULL, `right` VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, object_id INT DEFAULT NULL, `key` VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_topic_topic1_idx (topic_id), INDEX fk_topic_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE watching_user (post_id INT NOT NULL, user_id INT UNSIGNED NOT NULL, INDEX IDX_FFDC43024B89032C (post_id), INDEX IDX_FFDC4302A76ED395 (user_id), PRIMARY KEY(post_id, user_id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, topic_id INT DEFAULT NULL, user_id INT UNSIGNED NOT NULL, post_id INT DEFAULT NULL, title VARCHAR(450) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, text LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, creation_date DATETIME DEFAULT NULL, update_date DATETIME DEFAULT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX fk_post_post1_idx (post_id), INDEX fk_post_topic1_idx (topic_id), INDEX fk_post_user1_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE item_bak (id INT UNSIGNED AUTO_INCREMENT NOT NULL, quality_id INT NOT NULL, statut_id INT DEFAULT NULL, label VARCHAR(45) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, numero INT NOT NULL, identification INT NOT NULL, special LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, couleur VARCHAR(45) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, date_creation DATETIME NOT NULL, date_update DATETIME NOT NULL, discr VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, objet_id INT NOT NULL, quantite INT DEFAULT 1 NOT NULL, INDEX fk_item_statut1_idx (statut_id), INDEX fk_item_qualite1_idx (quality_id), INDEX fk_item_objet1_idx (objet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'ALTER TABLE post_view ADD CONSTRAINT FK_37A8CC854B89032C FOREIGN KEY (post_id) REFERENCES post (id)'
        );
        $this->addSql(
            'ALTER TABLE post_view ADD CONSTRAINT FK_37A8CC85A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
        );
        $this->addSql(
            'ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1B1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)'
        );
        $this->addSql(
            'ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
        );
        $this->addSql(
            'ALTER TABLE watching_user ADD CONSTRAINT FK_FFDC43024B89032C FOREIGN KEY (post_id) REFERENCES post (id)'
        );
        $this->addSql(
            'ALTER TABLE watching_user ADD CONSTRAINT FK_FFDC4302A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
        );
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql(
            'ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D1F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)'
        );
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE69354723BFF59E');
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE693547D0F97A8');
        $this->addSql('ALTER TABLE groupe_gn_ordre DROP FOREIGN KEY FK_BDA495F5FA640E02');
        $this->addSql('ALTER TABLE groupe_gn_ordre DROP FOREIGN KEY FK_BDA495F5A96E5E09');
        $this->addSql('ALTER TABLE qualite_valeur DROP FOREIGN KEY FK_8F9426EBA6338570');
        $this->addSql('ALTER TABLE qualite_valeur DROP FOREIGN KEY FK_8F9426EB98D3FE22');
        $this->addSql('DROP TABLE origine_bonus');
        $this->addSql('DROP TABLE groupe_gn_ordre');
        $this->addSql('DROP TABLE qualite');
        $this->addSql('DROP TABLE qualite_valeur');
        $this->addSql('DROP TABLE messenger_messages');

    }
}
