<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107122025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rulebook des règles de gameplay : domaines, règles et révisions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE rulebook_domaine (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, titre VARCHAR(150) NOT NULL, position INT DEFAULT 0 NOT NULL, UNIQUE INDEX uniq_rulebook_domaine_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE rulebook_regle (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, titre VARCHAR(200) NOT NULL, position INT DEFAULT 0 NOT NULL, texte_clair LONGTEXT DEFAULT NULL, detail_technique LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, tests_refs JSON NOT NULL, publiee TINYINT DEFAULT 0 NOT NULL, version INT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, domaine_id INT NOT NULL, updated_by_id INT UNSIGNED DEFAULT NULL, INDEX IDX_6C32881E4272FC9F (domaine_id), INDEX IDX_6C32881E896DBBDE (updated_by_id), INDEX idx_rulebook_regle_statut (statut), UNIQUE INDEX uniq_rulebook_regle_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE rulebook_revision (id INT AUTO_INCREMENT NOT NULL, version INT NOT NULL, titre VARCHAR(200) NOT NULL, texte_clair LONGTEXT DEFAULT NULL, detail_technique LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, publiee TINYINT DEFAULT 0 NOT NULL, motif VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, regle_id INT NOT NULL, auteur_id INT UNSIGNED DEFAULT NULL, INDEX IDX_A80589528E12947B (regle_id), INDEX IDX_A805895260BB6FE6 (auteur_id), UNIQUE INDEX uniq_rulebook_revision_regle_version (regle_id, version), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE rulebook_regle ADD CONSTRAINT FK_6C32881E4272FC9F FOREIGN KEY (domaine_id) REFERENCES rulebook_domaine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rulebook_regle ADD CONSTRAINT FK_6C32881E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE rulebook_revision ADD CONSTRAINT FK_A80589528E12947B FOREIGN KEY (regle_id) REFERENCES rulebook_regle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rulebook_revision ADD CONSTRAINT FK_A805895260BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rulebook_regle DROP FOREIGN KEY FK_6C32881E4272FC9F');
        $this->addSql('ALTER TABLE rulebook_regle DROP FOREIGN KEY FK_6C32881E896DBBDE');
        $this->addSql('ALTER TABLE rulebook_revision DROP FOREIGN KEY FK_A80589528E12947B');
        $this->addSql('ALTER TABLE rulebook_revision DROP FOREIGN KEY FK_A805895260BB6FE6');
        $this->addSql('DROP TABLE rulebook_domaine');
        $this->addSql('DROP TABLE rulebook_regle');
        $this->addSql('DROP TABLE rulebook_revision');
    }
}
