<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604132938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fiche_retour_groupe (id INT AUTO_INCREMENT NOT NULL, submitted_at DATETIME DEFAULT NULL, pieces_argent INT NOT NULL, pieces_or INT NOT NULL, nb_ingredients INT NOT NULL, nb_potions INT NOT NULL, armement INT NOT NULL, chevaux INT NOT NULL, fruits_legumes INT NOT NULL, matieres_simples INT NOT NULL, sel INT NOT NULL, betail INT NOT NULL, coton INT NOT NULL, gemmes INT NOT NULL, moutons INT NOT NULL, soie INT NOT NULL, bois INT NOT NULL, esclaves INT NOT NULL, ivoire INT NOT NULL, pierre INT NOT NULL, teinture INT NOT NULL, cereales INT NOT NULL, fourrures INT NOT NULL, matieres_precieux INT NOT NULL, poisson INT NOT NULL, vin INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, groupe_gn_id INT NOT NULL, created_by_id INT UNSIGNED DEFAULT NULL, updated_by_id INT UNSIGNED DEFAULT NULL, discr VARCHAR(255) NOT NULL, INDEX IDX_69721A4CB03A8386 (created_by_id), INDEX IDX_69721A4C896DBBDE (updated_by_id), UNIQUE INDEX uq_fiche_retour_groupe_gn (groupe_gn_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fiche_retour_groupe_history (id INT AUTO_INCREMENT NOT NULL, action_date DATETIME NOT NULL, action_type VARCHAR(20) NOT NULL, import_file_path VARCHAR(512) DEFAULT NULL, import_original_filename VARCHAR(255) DEFAULT NULL, data_before JSON DEFAULT NULL, data_after JSON NOT NULL, motif_type VARCHAR(20) DEFAULT NULL, motif LONGTEXT DEFAULT NULL, fiche_retour_groupe_id INT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, INDEX idx_fiche_history_fiche (fiche_retour_groupe_id), INDEX idx_fiche_history_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE fiche_retour_groupe ADD CONSTRAINT FK_69721A4CFA640E02 FOREIGN KEY (groupe_gn_id) REFERENCES groupe_gn (id)');
        $this->addSql('ALTER TABLE fiche_retour_groupe ADD CONSTRAINT FK_69721A4CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fiche_retour_groupe ADD CONSTRAINT FK_69721A4C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fiche_retour_groupe_history ADD CONSTRAINT FK_3CA63CE211E75B36 FOREIGN KEY (fiche_retour_groupe_id) REFERENCES fiche_retour_groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fiche_retour_groupe_history ADD CONSTRAINT FK_3CA63CE2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fiche_retour_groupe DROP FOREIGN KEY FK_69721A4CFA640E02');
        $this->addSql('ALTER TABLE fiche_retour_groupe DROP FOREIGN KEY FK_69721A4CB03A8386');
        $this->addSql('ALTER TABLE fiche_retour_groupe DROP FOREIGN KEY FK_69721A4C896DBBDE');
        $this->addSql('ALTER TABLE fiche_retour_groupe_history DROP FOREIGN KEY FK_3CA63CE211E75B36');
        $this->addSql('ALTER TABLE fiche_retour_groupe_history DROP FOREIGN KEY FK_3CA63CE2A76ED395');
        $this->addSql('DROP TABLE fiche_retour_groupe');
        $this->addSql('DROP TABLE fiche_retour_groupe_history');
    }
}
