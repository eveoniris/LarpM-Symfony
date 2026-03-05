<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Idempotent migration: adds missing FKs and fixes territoire columns.
 * All ADD CONSTRAINT / DROP INDEX / DROP FOREIGN KEY operations are
 * conditional so the migration is safe to run regardless of PROD state.
 */
final class Version20260303150041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing FKs (secondary_group, sort, sorts, religions_spheres, technologie, technologies_ressources, territoire and related tables, trigger, user, user_has_restriction) + fix territoire column types';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function fkExists(string $table, string $constraint): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
    }

    private function addFk(string $table, string $constraint, string $sql): void
    {
        if (!$this->fkExists($table, $constraint)) {
            try {
                $this->connection->executeStatement($sql);
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->warnIf(true, sprintf('Skipped FK %s on %s (incompatible types or missing ref): %s', $constraint, $table, $e->getMessage()));
            }
        }
    }

    private function dropFk(string $table, string $constraint): void
    {
        if ($this->fkExists($table, $constraint)) {
            $this->connection->executeStatement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function dropIndex(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            $this->connection->executeStatement("DROP INDEX `{$index}` ON `{$table}`");
        }
    }

    // -------------------------------------------------------------------------
    // Up
    // -------------------------------------------------------------------------

    public function up(Schema $schema): void
    {
        // competence_attribute: must drop FK before changing column, then restore
        $this->dropFk('competence_attribute', 'FK_CECF998615761DAB');
        $this->connection->executeStatement('ALTER TABLE competence_attribute CHANGE competence_id competence_id INT AUTO_INCREMENT');
        $this->addFk('competence_attribute', 'FK_CECF998615761DAB', 'ALTER TABLE competence_attribute ADD CONSTRAINT FK_CECF998615761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');

        $this->addFk('secondary_group', 'FK_717A91A31674CEC6', 'ALTER TABLE secondary_group ADD CONSTRAINT FK_717A91A31674CEC6 FOREIGN KEY (scenariste_id) REFERENCES user (id)');
        $this->addFk('sort', 'FK_5124F2224272FC9F', 'ALTER TABLE sort ADD CONSTRAINT FK_5124F2224272FC9F FOREIGN KEY (domaine_id) REFERENCES domaine (id)');
        $this->addFk('sorts', 'FK_CE3FAA1D4272FC9F', 'ALTER TABLE sorts ADD CONSTRAINT FK_CE3FAA1D4272FC9F FOREIGN KEY (domaine_id) REFERENCES domaine (id)');
        $this->addFk('religions_spheres', 'FK_65855EBE75FD4EF9', 'ALTER TABLE religions_spheres ADD CONSTRAINT FK_65855EBE75FD4EF9 FOREIGN KEY (sphere_id) REFERENCES sphere (id)');
        $this->addFk('religions_spheres', 'FK_65855EBEB7850CBD', 'ALTER TABLE religions_spheres ADD CONSTRAINT FK_65855EBEB7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addFk('technologie', 'FK_AD813674F7EB2017', 'ALTER TABLE technologie ADD CONSTRAINT FK_AD813674F7EB2017 FOREIGN KEY (competence_family_id) REFERENCES competence_family (id)');
        $this->addFk('technologies_ressources', 'FK_B15E3D68261A27D2', 'ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68261A27D2 FOREIGN KEY (technologie_id) REFERENCES technologie (id)');
        $this->addFk('technologies_ressources', 'FK_B15E3D68FC6CD52A', 'ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');

        $this->dropIndex('territoire', 'UNIQ_B8655F546DE44026');
        $this->dropIndex('territoire', 'UNIQ_B8655F54D05F003D');
        $this->dropIndex('territoire', 'UNIQ_B8655F54C53F9EA');
        $this->dropIndex('territoire', 'UNIQ_B8655F54BEC71E71');
        $this->addSql('ALTER TABLE territoire CHANGE description description LONGTEXT DEFAULT NULL, CHANGE capitale capitale VARCHAR(45) DEFAULT NULL, CHANGE politique politique VARCHAR(45) DEFAULT NULL, CHANGE dirigeant dirigeant VARCHAR(45) DEFAULT NULL, CHANGE type_racial type_racial LONGTEXT DEFAULT NULL, CHANGE inspiration inspiration LONGTEXT DEFAULT NULL, CHANGE armes_predilection armes_predilection LONGTEXT DEFAULT NULL, CHANGE vetements vetements LONGTEXT DEFAULT NULL, CHANGE noms_masculin noms_masculin LONGTEXT DEFAULT NULL, CHANGE noms_feminin noms_feminin LONGTEXT DEFAULT NULL, CHANGE frontieres frontieres LONGTEXT DEFAULT NULL, CHANGE geojson geojson LONGTEXT DEFAULT NULL, CHANGE statut statut VARCHAR(45) DEFAULT NULL');

        $this->addFk('territoire', 'FK_B8655F54D0F97A8', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire', 'FK_B8655F54682CA693', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54682CA693 FOREIGN KEY (territoire_guerre_id) REFERENCES territoire_guerre (id)');
        $this->addFk('territoire', 'FK_B8655F54F9E65DDB', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54F9E65DDB FOREIGN KEY (appelation_id) REFERENCES appelation (id)');
        $this->addFk('territoire', 'FK_B8655F542AADBACD', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F542AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id)');
        $this->addFk('territoire', 'FK_B8655F54B7850CBD', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addFk('territoire', 'FK_B8655F547A45358C', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F547A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
        $this->addFk('territoire', 'FK_B8655F54B108249D', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->addFk('territoire_quete', 'FK_63718DCD0F97A8', 'ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_quete', 'FK_63718DCB011823', 'ALTER TABLE territoire_quete ADD CONSTRAINT FK_63718DCB011823 FOREIGN KEY (territoire_cible_id) REFERENCES territoire (id)');
        $this->addFk('territoire_has_construction', 'FK_FEB4D8E9D0F97A8', 'ALTER TABLE territoire_has_construction ADD CONSTRAINT FK_FEB4D8E9D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_has_construction', 'FK_FEB4D8E9CF48117A', 'ALTER TABLE territoire_has_construction ADD CONSTRAINT FK_FEB4D8E9CF48117A FOREIGN KEY (construction_id) REFERENCES construction (id)');
        $this->addFk('territoire_has_loi', 'FK_5470401BD0F97A8', 'ALTER TABLE territoire_has_loi ADD CONSTRAINT FK_5470401BD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_has_loi', 'FK_5470401BAB82AB5', 'ALTER TABLE territoire_has_loi ADD CONSTRAINT FK_5470401BAB82AB5 FOREIGN KEY (loi_id) REFERENCES loi (id)');
        $this->addFk('territoire_ingredient', 'FK_9B7BF292D0F97A8', 'ALTER TABLE territoire_ingredient ADD CONSTRAINT FK_9B7BF292D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_ingredient', 'FK_9B7BF292933FE08C', 'ALTER TABLE territoire_ingredient ADD CONSTRAINT FK_9B7BF292933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addFk('territoire_importation', 'FK_77B99CF6D0F97A8', 'ALTER TABLE territoire_importation ADD CONSTRAINT FK_77B99CF6D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_importation', 'FK_77B99CF6FC6CD52A', 'ALTER TABLE territoire_importation ADD CONSTRAINT FK_77B99CF6FC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addFk('territoire_exportation', 'FK_BC24449DD0F97A8', 'ALTER TABLE territoire_exportation ADD CONSTRAINT FK_BC24449DD0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_exportation', 'FK_BC24449DFC6CD52A', 'ALTER TABLE territoire_exportation ADD CONSTRAINT FK_BC24449DFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addFk('territoire_langue', 'FK_C9327BC3D0F97A8', 'ALTER TABLE territoire_langue ADD CONSTRAINT FK_C9327BC3D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_langue', 'FK_C9327BC32AADBACD', 'ALTER TABLE territoire_langue ADD CONSTRAINT FK_C9327BC32AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id)');
        $this->addFk('territoire_religion', 'FK_B23AB2D3D0F97A8', 'ALTER TABLE territoire_religion ADD CONSTRAINT FK_B23AB2D3D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('territoire_religion', 'FK_B23AB2D3B7850CBD', 'ALTER TABLE territoire_religion ADD CONSTRAINT FK_B23AB2D3B7850CBD FOREIGN KEY (religion_id) REFERENCES religion (id)');
        $this->addFk('titre_territoire', 'FK_FA93160ED54FAE5E', 'ALTER TABLE titre_territoire ADD CONSTRAINT FK_FA93160ED54FAE5E FOREIGN KEY (titre_id) REFERENCES titre (id)');
        $this->addFk('titre_territoire', 'FK_FA93160ED0F97A8', 'ALTER TABLE titre_territoire ADD CONSTRAINT FK_FA93160ED0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addFk('trigger', 'FK_1A6B0F5D5E315342', 'ALTER TABLE `trigger` ADD CONSTRAINT FK_1A6B0F5D5E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addFk('user', 'FK_8D93D649191476EE', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649191476EE FOREIGN KEY (etat_civil_id) REFERENCES etat_civil (id)');
        $this->addFk('user', 'FK_8D93D649E6917FB3', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage (id)');
        $this->addFk('user', 'FK_8D93D6495E315342', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D6495E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addFk('user_has_restriction', 'FK_1A57746A76ED395', 'ALTER TABLE user_has_restriction ADD CONSTRAINT FK_1A57746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addFk('user_has_restriction', 'FK_1A57746E6160631', 'ALTER TABLE user_has_restriction ADD CONSTRAINT FK_1A57746E6160631 FOREIGN KEY (restriction_id) REFERENCES restriction (id)');
    }

    // -------------------------------------------------------------------------
    // Down
    // -------------------------------------------------------------------------

    public function down(Schema $schema): void
    {
        $this->dropFk('competence_attribute', 'FK_CECF998615761DAB');
        $this->connection->executeStatement('ALTER TABLE competence_attribute CHANGE competence_id competence_id INT AUTO_INCREMENT NOT NULL');
        $this->addFk('competence_attribute', 'FK_CECF998615761DAB', 'ALTER TABLE competence_attribute ADD CONSTRAINT FK_CECF998615761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');

        $this->dropFk('religions_spheres', 'FK_65855EBE75FD4EF9');
        $this->dropFk('religions_spheres', 'FK_65855EBEB7850CBD');
        $this->dropFk('secondary_group', 'FK_717A91A31674CEC6');
        $this->dropFk('sort', 'FK_5124F2224272FC9F');
        $this->dropFk('sorts', 'FK_CE3FAA1D4272FC9F');
        $this->dropFk('technologie', 'FK_AD813674F7EB2017');
        $this->dropFk('technologies_ressources', 'FK_B15E3D68261A27D2');
        $this->dropFk('technologies_ressources', 'FK_B15E3D68FC6CD52A');
        $this->dropFk('territoire', 'FK_B8655F54D0F97A8');
        $this->dropFk('territoire', 'FK_B8655F54682CA693');
        $this->dropFk('territoire', 'FK_B8655F54F9E65DDB');
        $this->dropFk('territoire', 'FK_B8655F542AADBACD');
        $this->dropFk('territoire', 'FK_B8655F54B7850CBD');
        $this->dropFk('territoire', 'FK_B8655F547A45358C');
        $this->dropFk('territoire', 'FK_B8655F54B108249D');
        $this->addSql('ALTER TABLE territoire CHANGE description description VARCHAR(180) NOT NULL, CHANGE capitale capitale VARCHAR(45) NOT NULL, CHANGE politique politique VARCHAR(45) NOT NULL, CHANGE dirigeant dirigeant VARCHAR(45) NOT NULL, CHANGE type_racial type_racial VARCHAR(255) DEFAULT NULL, CHANGE inspiration inspiration VARCHAR(255) DEFAULT NULL, CHANGE armes_predilection armes_predilection VARCHAR(255) DEFAULT NULL, CHANGE vetements vetements VARCHAR(255) DEFAULT NULL, CHANGE noms_masculin noms_masculin VARCHAR(255) DEFAULT NULL, CHANGE noms_feminin noms_feminin VARCHAR(255) DEFAULT NULL, CHANGE frontieres frontieres VARCHAR(255) DEFAULT NULL, CHANGE geojson geojson VARCHAR(255) DEFAULT NULL, CHANGE statut statut TINYTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F546DE44026 ON territoire (description)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54D05F003D ON territoire (capitale)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54C53F9EA ON territoire (politique)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8655F54BEC71E71 ON territoire (dirigeant)');
        $this->dropFk('territoire_exportation', 'FK_BC24449DD0F97A8');
        $this->dropFk('territoire_exportation', 'FK_BC24449DFC6CD52A');
        $this->dropFk('territoire_has_construction', 'FK_FEB4D8E9D0F97A8');
        $this->dropFk('territoire_has_construction', 'FK_FEB4D8E9CF48117A');
        $this->dropFk('territoire_has_loi', 'FK_5470401BD0F97A8');
        $this->dropFk('territoire_has_loi', 'FK_5470401BAB82AB5');
        $this->dropFk('territoire_importation', 'FK_77B99CF6D0F97A8');
        $this->dropFk('territoire_importation', 'FK_77B99CF6FC6CD52A');
        $this->dropFk('territoire_ingredient', 'FK_9B7BF292D0F97A8');
        $this->dropFk('territoire_ingredient', 'FK_9B7BF292933FE08C');
        $this->dropFk('territoire_langue', 'FK_C9327BC3D0F97A8');
        $this->dropFk('territoire_langue', 'FK_C9327BC32AADBACD');
        $this->dropFk('territoire_quete', 'FK_63718DCD0F97A8');
        $this->dropFk('territoire_quete', 'FK_63718DCB011823');
        $this->dropFk('territoire_religion', 'FK_B23AB2D3D0F97A8');
        $this->dropFk('territoire_religion', 'FK_B23AB2D3B7850CBD');
        $this->dropFk('titre_territoire', 'FK_FA93160ED54FAE5E');
        $this->dropFk('titre_territoire', 'FK_FA93160ED0F97A8');
        $this->dropFk('trigger', 'FK_1A6B0F5D5E315342');
        $this->dropFk('user', 'FK_8D93D649191476EE');
        $this->dropFk('user', 'FK_8D93D649E6917FB3');
        $this->dropFk('user', 'FK_8D93D6495E315342');
        $this->dropFk('user_has_restriction', 'FK_1A57746A76ED395');
        $this->dropFk('user_has_restriction', 'FK_1A57746E6160631');
    }
}
