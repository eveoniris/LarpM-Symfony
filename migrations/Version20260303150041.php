<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303150041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence_attribute CHANGE competence_id competence_id INT AUTO_INCREMENT');
        $this->addSql('ALTER TABLE secondary_group ADD CONSTRAINT FK_717A91A31674CEC6 FOREIGN KEY (scenariste_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sort ADD CONSTRAINT FK_5124F2224272FC9F FOREIGN KEY (domaine_id) REFERENCES domaine (id)');
        $this->addSql('ALTER TABLE sorts ADD CONSTRAINT FK_CE3FAA1D4272FC9F FOREIGN KEY (domaine_id) REFERENCES domaine (id)');
        $this->addSql('ALTER TABLE religions_spheres ADD CONSTRAINT FK_65855EBE75FD4EF9 FOREIGN KEY (sphere_id) REFERENCES sphere (id)');
        $this->addSql('ALTER TABLE religions_spheres ADD CONSTRAINT FK_65855EBEB7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addSql('ALTER TABLE technologie ADD CONSTRAINT FK_AD813674F7EB2017 FOREIGN KEY (competence_family_id) REFERENCES competence_family (id)');
        $this->addSql('ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68261A27D2 FOREIGN KEY (technologie_id) REFERENCES technologie (id)');
        $this->addSql('ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('DROP INDEX UNIQ_B8655F546DE44026 ON territoire');
        $this->addSql('DROP INDEX UNIQ_B8655F54D05F003D ON territoire');
        $this->addSql('DROP INDEX UNIQ_B8655F54C53F9EA ON territoire');
        $this->addSql('DROP INDEX UNIQ_B8655F54BEC71E71 ON territoire');
        $this->addSql('ALTER TABLE territoire CHANGE description description LONGTEXT DEFAULT NULL, CHANGE capitale capitale VARCHAR(45) DEFAULT NULL, CHANGE politique politique VARCHAR(45) DEFAULT NULL, CHANGE dirigeant dirigeant VARCHAR(45) DEFAULT NULL, CHANGE type_racial type_racial LONGTEXT DEFAULT NULL, CHANGE inspiration inspiration LONGTEXT DEFAULT NULL, CHANGE armes_predilection armes_predilection LONGTEXT DEFAULT NULL, CHANGE vetements vetements LONGTEXT DEFAULT NULL, CHANGE noms_masculin noms_masculin LONGTEXT DEFAULT NULL, CHANGE noms_feminin noms_feminin LONGTEXT DEFAULT NULL, CHANGE frontieres frontieres LONGTEXT DEFAULT NULL, CHANGE geojson geojson LONGTEXT DEFAULT NULL, CHANGE statut statut VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54682CA693 FOREIGN KEY (territoire_guerre_id) REFERENCES territoire_guerre (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54F9E65DDB FOREIGN KEY (appelation_id) REFERENCES appelation (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F542AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F547A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
        $this->addSql('ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCB011823 FOREIGN KEY (territoire_cible_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_has_construction ADD CONSTRAINT FK_FEB4D8E9D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_has_construction ADD CONSTRAINT FK_FEB4D8E9CF48117A FOREIGN KEY (construction_id) REFERENCES construction (id)');
        $this->addSql('ALTER TABLE territoire_has_loi ADD CONSTRAINT FK_5470401BD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_has_loi ADD CONSTRAINT FK_5470401BAB82AB5 FOREIGN KEY (loi_id) REFERENCES loi (id)');
        $this->addSql('ALTER TABLE territoire_ingredient ADD CONSTRAINT FK_9B7BF292D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_ingredient ADD CONSTRAINT FK_9B7BF292933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE territoire_importation ADD CONSTRAINT FK_77B99CF6D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_importation ADD CONSTRAINT FK_77B99CF6FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE territoire_exportation ADD CONSTRAINT FK_BC24449DD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_exportation ADD CONSTRAINT FK_BC24449DFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE territoire_langue ADD CONSTRAINT FK_C9327BC3D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_langue ADD CONSTRAINT FK_C9327BC32AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id)');
        $this->addSql('ALTER TABLE territoire_religion ADD CONSTRAINT FK_B23AB2D3D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE territoire_religion ADD CONSTRAINT FK_B23AB2D3B7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addSql('ALTER TABLE titre_territoire ADD CONSTRAINT FK_FA93160ED54FAE5E FOREIGN KEY (titre_id) REFERENCES titre (id)');
        $this->addSql('ALTER TABLE titre_territoire ADD CONSTRAINT FK_FA93160ED0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE `trigger` ADD CONSTRAINT FK_1A6B0F5D5E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649191476EE FOREIGN KEY (etat_civil_id) REFERENCES etat_civil (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6495E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE user_has_restriction ADD CONSTRAINT FK_1A57746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_has_restriction ADD CONSTRAINT FK_1A57746E6160631 FOREIGN KEY (restriction_id) REFERENCES restriction (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence_attribute CHANGE competence_id competence_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE religions_spheres DROP FOREIGN KEY FK_65855EBE75FD4EF9');
        $this->addSql('ALTER TABLE religions_spheres DROP FOREIGN KEY FK_65855EBEB7850CBD');
        $this->addSql('ALTER TABLE secondary_group DROP FOREIGN KEY FK_717A91A31674CEC6');
        $this->addSql('ALTER TABLE sort DROP FOREIGN KEY FK_5124F2224272FC9F');
        $this->addSql('ALTER TABLE sorts DROP FOREIGN KEY FK_CE3FAA1D4272FC9F');
        $this->addSql('ALTER TABLE technologie DROP FOREIGN KEY FK_AD813674F7EB2017');
        $this->addSql('ALTER TABLE technologies_ressources DROP FOREIGN KEY FK_B15E3D68261A27D2');
        $this->addSql('ALTER TABLE technologies_ressources DROP FOREIGN KEY FK_B15E3D68FC6CD52A');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F54D0F97A8');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F54682CA693');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F54F9E65DDB');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F542AADBACD');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F54B7850CBD');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F547A45358C');
        $this->addSql('ALTER TABLE territoire DROP FOREIGN KEY FK_B8655F54B108249D');
        $this->addSql('ALTER TABLE territoire CHANGE description description VARCHAR(180) NOT NULL, CHANGE capitale capitale VARCHAR(45) NOT NULL, CHANGE politique politique VARCHAR(45) NOT NULL, CHANGE dirigeant dirigeant VARCHAR(45) NOT NULL, CHANGE type_racial type_racial VARCHAR(255) DEFAULT NULL, CHANGE inspiration inspiration VARCHAR(255) DEFAULT NULL, CHANGE armes_predilection armes_predilection VARCHAR(255) DEFAULT NULL, CHANGE vetements vetements VARCHAR(255) DEFAULT NULL, CHANGE noms_masculin noms_masculin VARCHAR(255) DEFAULT NULL, CHANGE noms_feminin noms_feminin VARCHAR(255) DEFAULT NULL, CHANGE frontieres frontieres VARCHAR(255) DEFAULT NULL, CHANGE geojson geojson VARCHAR(255) DEFAULT NULL, CHANGE statut statut TINYTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F546DE44026 ON territoire (description)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54D05F003D ON territoire (capitale)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54C53F9EA ON territoire (politique)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54BEC71E71 ON territoire (dirigeant)');
        $this->addSql('ALTER TABLE territoire_exportation DROP FOREIGN KEY FK_BC24449DD0F97A8');
        $this->addSql('ALTER TABLE territoire_exportation DROP FOREIGN KEY FK_BC24449DFC6CD52A');
        $this->addSql('ALTER TABLE territoire_has_construction DROP FOREIGN KEY FK_FEB4D8E9D0F97A8');
        $this->addSql('ALTER TABLE territoire_has_construction DROP FOREIGN KEY FK_FEB4D8E9CF48117A');
        $this->addSql('ALTER TABLE territoire_has_loi DROP FOREIGN KEY FK_5470401BD0F97A8');
        $this->addSql('ALTER TABLE territoire_has_loi DROP FOREIGN KEY FK_5470401BAB82AB5');
        $this->addSql('ALTER TABLE territoire_importation DROP FOREIGN KEY FK_77B99CF6D0F97A8');
        $this->addSql('ALTER TABLE territoire_importation DROP FOREIGN KEY FK_77B99CF6FC6CD52A');
        $this->addSql('ALTER TABLE territoire_ingredient DROP FOREIGN KEY FK_9B7BF292D0F97A8');
        $this->addSql('ALTER TABLE territoire_ingredient DROP FOREIGN KEY FK_9B7BF292933FE08C');
        $this->addSql('ALTER TABLE territoire_langue DROP FOREIGN KEY FK_C9327BC3D0F97A8');
        $this->addSql('ALTER TABLE territoire_langue DROP FOREIGN KEY FK_C9327BC32AADBACD');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCD0F97A8');
        $this->addSql('ALTER TABLE territoire_quete DROP FOREIGN KEY FK_63718DCB011823');
        $this->addSql('ALTER TABLE territoire_religion DROP FOREIGN KEY FK_B23AB2D3D0F97A8');
        $this->addSql('ALTER TABLE territoire_religion DROP FOREIGN KEY FK_B23AB2D3B7850CBD');
        $this->addSql('ALTER TABLE titre_territoire DROP FOREIGN KEY FK_FA93160ED54FAE5E');
        $this->addSql('ALTER TABLE titre_territoire DROP FOREIGN KEY FK_FA93160ED0F97A8');
        $this->addSql('ALTER TABLE `trigger` DROP FOREIGN KEY FK_1A6B0F5D5E315342');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649191476EE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E6917FB3');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6495E315342');
        $this->addSql('ALTER TABLE user_has_restriction DROP FOREIGN KEY FK_1A57746A76ED395');
        $this->addSql('ALTER TABLE user_has_restriction DROP FOREIGN KEY FK_1A57746E6160631');
    }
}
