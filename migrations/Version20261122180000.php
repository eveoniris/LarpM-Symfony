<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Report du bonus d'XP d'un personnage mort (règles XP-05 à XP-07).
 */
final class Version20261122180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table xp_report : report du bonus d\'XP à la mort';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE xp_report (id INT AUTO_INCREMENT NOT NULL, personnage_mort_id INT NOT NULL, personnage_beneficiaire_id INT NOT NULL, experience_gain_id INT DEFAULT NULL, auteur_id INT UNSIGNED DEFAULT NULL, mode VARCHAR(10) NOT NULL, xp_base INT NOT NULL, xp_reporte INT NOT NULL, `force` TINYINT DEFAULT 0 NOT NULL, motif VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_xp_report_mort (personnage_mort_id), UNIQUE INDEX uniq_xp_report_beneficiaire (personnage_beneficiaire_id), UNIQUE INDEX UNIQ_A152AB8A83251356 (experience_gain_id), INDEX IDX_A152AB8A60BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
        );
        $this->addSql('ALTER TABLE xp_report ADD CONSTRAINT FK_XP_REPORT_MORT FOREIGN KEY (personnage_mort_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE xp_report ADD CONSTRAINT FK_XP_REPORT_BENEF FOREIGN KEY (personnage_beneficiaire_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE xp_report ADD CONSTRAINT FK_XP_REPORT_GAIN FOREIGN KEY (experience_gain_id) REFERENCES experience_gain (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE xp_report ADD CONSTRAINT FK_A152AB8A60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE xp_report');
    }
}
