<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Conceptions d heritiers signalees dans les retours de jeu (table conception)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE conception (id INT AUTO_INCREMENT NOT NULL, simulation TINYINT DEFAULT 0 NOT NULL, tirage SMALLINT DEFAULT NULL, issue VARCHAR(30) DEFAULT NULL, statut VARCHAR(20) NOT NULL, anomalies JSON NOT NULL, remarque LONGTEXT DEFAULT NULL, heritiers JSON NOT NULL, created_at DATETIME NOT NULL, decide_at DATETIME DEFAULT NULL, gn_id INT NOT NULL, parent1_id INT NOT NULL, parent2_id INT NOT NULL, depouillement_id INT DEFAULT NULL, cree_par_id INT UNSIGNED DEFAULT NULL, decide_par_id INT UNSIGNED DEFAULT NULL, INDEX IDX_6F60FFF4AFC9C052 (gn_id), INDEX IDX_6F60FFF4861B2665 (parent1_id), INDEX IDX_6F60FFF494AE898B (parent2_id), INDEX IDX_6F60FFF4246EFAD2 (depouillement_id), INDEX IDX_6F60FFF4FC29C013 (cree_par_id), INDEX IDX_6F60FFF42E54F2F1 (decide_par_id), INDEX idx_conception_gn_statut (gn_id, statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF4AFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF4861B2665 FOREIGN KEY (parent1_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF494AE898B FOREIGN KEY (parent2_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF4246EFAD2 FOREIGN KEY (depouillement_id) REFERENCES depouillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF4FC29C013 FOREIGN KEY (cree_par_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conception ADD CONSTRAINT FK_6F60FFF42E54F2F1 FOREIGN KEY (decide_par_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF4AFC9C052');
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF4861B2665');
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF494AE898B');
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF4246EFAD2');
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF4FC29C013');
        $this->addSql('ALTER TABLE conception DROP FOREIGN KEY FK_6F60FFF42E54F2F1');
        $this->addSql('DROP TABLE conception');
    }
}
