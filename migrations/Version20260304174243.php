<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Idempotent migration: schema alignment phase 2.
 * All RENAME INDEX / ADD CONSTRAINT / DROP FOREIGN KEY / DROP INDEX / CREATE INDEX
 * operations are conditional. All nullable→NOT NULL and text-truncation changes use
 * safeAlter() to skip gracefully if data prevents the change (logs warning instead of failing).
 * REQUIRES Version20260303150041 to have run first.
 */
final class Version20260304174243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema alignment phase 2: username VARCHAR(100), DROP is_enabled, unsigned IDs, index renames, accumulated DEV→PROD drift. Requires V20260303150041.';
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

    private function columnExists(string $table, string $column): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    /**
     * Safe ALTER: warns and skips instead of failing.
     * Use for: nullable→NOT NULL changes, text truncation, UNSIGNED→signed PKs with FK refs.
     */
    private function safeAlter(string $table, string $sql): void
    {
        try {
            $this->connection->executeStatement($sql);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->warnIf(true, sprintf('Skipped ALTER on %s (data incompatibility): %s', $table, $e->getMessage()));
        }
    }

    /**
     * Drop all FK constraints (from any table) that reference a given table+column.
     * Use before changing the type of a referenced PK column (UNSIGNED→signed, etc.).
     */
    private function dropAllFksReferencing(string $referencedTable, string $referencedColumn): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT kcu.TABLE_NAME, kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
              AND kcu.TABLE_SCHEMA    = tc.TABLE_SCHEMA
              AND kcu.TABLE_NAME      = tc.TABLE_NAME
             WHERE kcu.TABLE_SCHEMA        = DATABASE()
               AND kcu.REFERENCED_TABLE_NAME  = ?
               AND kcu.REFERENCED_COLUMN_NAME = ?
               AND tc.CONSTRAINT_TYPE         = ?',
            [$referencedTable, $referencedColumn, 'FOREIGN KEY']
        );
        foreach ($rows as $row) {
            $this->dropFk($row['TABLE_NAME'], $row['CONSTRAINT_NAME']);
        }
    }

    private function renameIndex(string $table, string $from, string $to): void
    {
        if ($this->indexExists($table, $from) && !$this->indexExists($table, $to)) {
            $this->connection->executeStatement("ALTER TABLE `{$table}` RENAME INDEX `{$from}` TO `{$to}`");
        }
    }

    private function dropIndex(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            $this->connection->executeStatement("DROP INDEX `{$index}` ON `{$table}`");
        }
    }

    private function addIndex(string $table, string $index, string $sql): void
    {
        if (!$this->indexExists($table, $index)) {
            try {
                $this->connection->executeStatement($sql);
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->warnIf(true, sprintf('Skipped index %s on %s: %s', $index, $table, $e->getMessage()));
            }
        }
    }

    private function dropFk(string $table, string $constraint): void
    {
        if ($this->fkExists($table, $constraint)) {
            $this->connection->executeStatement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function addFk(string $table, string $constraint, string $sql): void
    {
        if (!$this->fkExists($table, $constraint)) {
            try {
                $this->connection->executeStatement($sql);
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->warnIf(true, sprintf('Skipped FK %s on %s: %s', $constraint, $table, $e->getMessage()));
            }
        }
    }

    // -------------------------------------------------------------------------
    // Up
    // -------------------------------------------------------------------------

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE background CHANGE visibility visibility VARCHAR(255) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE billet CHANGE label label VARCHAR(255) NOT NULL');
        $this->renameIndex('billet', 'fk_billet_user1_idx', 'fk_billet_user1');
        $this->connection->executeStatement('ALTER TABLE bonus CHANGE json_data json_data JSON DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE competence CHANGE documentUrl documentUrl TINYTEXT DEFAULT NULL');
        $this->safeAlter('competence_attribute', 'ALTER TABLE competence_attribute DROP PRIMARY KEY, ADD PRIMARY KEY (competence_id, attribute_type_id, value)');
        // connaissance: documentUrl varchar(255)→varchar(45) may truncate data
        $this->safeAlter('connaissance', 'ALTER TABLE connaissance CHANGE label label VARCHAR(45) NOT NULL, CHANGE contraintes contraintes LONGTEXT DEFAULT NULL, CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL, CHANGE niveau niveau INT NOT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        // Drop all FKs referencing UNSIGNED PKs before changing their types to signed.
        // This prevents MySQL 3780 "incompatible types in FK constraint" errors.
        $this->dropAllFksReferencing('culture', 'id');
        $this->dropAllFksReferencing('evenement', 'id');
        $this->dropAllFksReferencing('item', 'id');
        $this->dropAllFksReferencing('intrigue', 'id');
        $this->dropAllFksReferencing('loi', 'id');
        $this->dropAllFksReferencing('lignees', 'id');
        $this->dropAllFksReferencing('notification', 'id');
        $this->dropAllFksReferencing('objectif', 'id');
        $this->dropAllFksReferencing('quality_valeur', 'id');
        $this->safeAlter('culture', 'ALTER TABLE culture CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // debriefing.player_id nullable→NOT NULL: may fail if NULL values exist in data
        $this->safeAlter('debriefing', 'ALTER TABLE debriefing CHANGE player_id player_id INT UNSIGNED NOT NULL');
        $this->addFk('debriefing', 'FK_CB81225E99E6F5DF', 'ALTER TABLE debriefing ADD CONSTRAINT FK_CB81225E99E6F5DF FOREIGN KEY (player_id) REFERENCES user (id)');
        $this->renameIndex('debriefing', 'fk_debriefing_player1_idx', 'IDX_CB81225E99E6F5DF');
        $this->connection->executeStatement('ALTER TABLE document CHANGE creation_date creation_date DATETIME DEFAULT NULL, CHANGE update_date update_date DATETIME DEFAULT NULL');
        // evenement.id UNSIGNED→signed PK; may fail if FKs reference it with UNSIGNED type
        $this->safeAlter('evenement', 'ALTER TABLE evenement CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // Create new personnage_id index FIRST so the FK has coverage, then drop the old one
        $this->addIndex('experience_usage', 'IDX_B3B922665E315342', 'CREATE INDEX IDX_B3B922665E315342 ON experience_usage (personnage_id)');
        $this->dropIndex('experience_usage', 'fk_experience_usage_personnage1_idx');
        $this->addIndex('experience_usage', 'fk_experience_usage_personnage1_idx', 'CREATE INDEX fk_experience_usage_personnage1_idx ON experience_usage (competence_id)');
        // formulaire.id UNSIGNED→signed PK
        $this->safeAlter('formulaire', 'ALTER TABLE formulaire CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE gn CHANGE date_jeu date_jeu INT DEFAULT NULL');
        // groupe.territoire_id nullable→NOT NULL
        $this->safeAlter('groupe', 'ALTER TABLE groupe CHANGE territoire_id territoire_id INT NOT NULL, CHANGE description_membres description_membres LONGTEXT DEFAULT NULL');
        // item.id was UNSIGNED; change it first (FKs already dropped above), then referencing columns
        $this->safeAlter('item', 'ALTER TABLE item CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE quality_id quality_id INT DEFAULT NULL, CHANGE statut_id statut_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_item CHANGE item_id item_id INT NOT NULL');
        // groupe_allie.message_allie LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('groupe_allie', 'ALTER TABLE groupe_allie CHANGE message_allie message_allie VARCHAR(255) DEFAULT NULL');
        // groupe_gn: responsable_id/place_available nullable→NOT NULL
        $this->safeAlter('groupe_gn', 'ALTER TABLE groupe_gn CHANGE responsable_id responsable_id INT NOT NULL, CHANGE place_available place_available INT NOT NULL, CHANGE agents agents INT NOT NULL, CHANGE bateaux bateaux INT NOT NULL, CHANGE sieges sieges INT NOT NULL, CHANGE initiative initiative INT NOT NULL');
        $this->addFk('groupe_gn', 'FK_413F11C8EAED05C', 'ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C8EAED05C FOREIGN KEY (diplomate_id) REFERENCES personnage (id)');
        $this->addIndex('groupe_gn', 'IDX_413F11C8EAED05C', 'CREATE INDEX IDX_413F11C8EAED05C ON groupe_gn (diplomate_id)');
        // groupe_gn_ordre nullable→NOT NULL
        $this->safeAlter('groupe_gn_ordre', 'ALTER TABLE groupe_gn_ordre CHANGE groupe_gn_id groupe_gn_id INT NOT NULL, CHANGE cible_id cible_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_ingredient CHANGE groupe_id groupe_id INT DEFAULT NULL, CHANGE ingredient_id ingredient_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_ressource CHANGE groupe_id groupe_id INT DEFAULT NULL, CHANGE ressource_id ressource_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_langue CHANGE label label VARCHAR(180) NOT NULL');
        $this->addIndex('groupe_langue', 'UNIQ_F86989B4EA750E8', 'CREATE UNIQUE INDEX UNIQ_F86989B4EA750E8 ON groupe_langue (label)');
        // heroisme_history: explication LONGTEXT→VARCHAR(255) may truncate; date DATE→DATETIME
        $this->safeAlter('heroisme_history', 'ALTER TABLE heroisme_history CHANGE personnage_id personnage_id INT DEFAULT NULL, CHANGE date date DATETIME NOT NULL, CHANGE explication explication VARCHAR(255) NOT NULL');
        // ingredient.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('ingredient', 'ALTER TABLE ingredient CHANGE description description VARCHAR(255) DEFAULT NULL');
        // intrigue: id UNSIGNED→signed, resolution nullable→NOT NULL
        $this->safeAlter('intrigue', 'ALTER TABLE intrigue CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE resolution resolution LONGTEXT NOT NULL');
        // intrigue_has_document: id UNSIGNED→signed, intrigue_id UNSIGNED→signed
        $this->safeAlter('intrigue_has_document', 'ALTER TABLE intrigue_has_document CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_evenement CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT DEFAULT NULL, CHANGE evenement_id evenement_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_groupe CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT DEFAULT NULL, CHANGE groupe_id groupe_id INT DEFAULT NULL');
        $this->safeAlter('intrigue_has_groupe_secondaire', 'ALTER TABLE intrigue_has_groupe_secondaire CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL');
        $this->safeAlter('intrigue_has_lieu', 'ALTER TABLE intrigue_has_lieu CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL');
        $this->safeAlter('intrigue_has_modification', 'ALTER TABLE intrigue_has_modification CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL');
        $this->safeAlter('intrigue_has_objectif', 'ALTER TABLE intrigue_has_objectif CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL, CHANGE objectif_id objectif_id INT NOT NULL');
        $this->addFk('langue', 'FK_9357758E7A3969FA', 'ALTER TABLE langue ADD CONSTRAINT FK_9357758E7A3969FA FOREIGN KEY (groupe_langue_id) REFERENCES groupe_langue (id)');
        $this->addIndex('level', 'UNIQ_9AEACC134F2FFF1C', 'CREATE UNIQUE INDEX UNIQ_9AEACC134F2FFF1C ON level (`index`)');
        $this->safeAlter('lignees', 'ALTER TABLE lignees CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        // localisation.precision nullable→NOT NULL
        $this->safeAlter('localisation', 'ALTER TABLE localisation CHANGE `precision` `precision` VARCHAR(450) NOT NULL');
        $this->safeAlter('loi', 'ALTER TABLE loi CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // message.lu nullable→NOT NULL
        $this->safeAlter('message', 'ALTER TABLE message CHANGE lu lu TINYINT(1) NOT NULL');
        $this->safeAlter('notification', 'ALTER TABLE notification CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->renameIndex('notification', 'fk_notification_user1_idx', 'IDX_BF5476CAA76ED395');
        $this->safeAlter('objectif', 'ALTER TABLE objectif CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // objet: proprietaire_id/responsable_id nullable→NOT NULL
        $this->safeAlter('objet', 'ALTER TABLE objet CHANGE proprietaire_id proprietaire_id INT NOT NULL, CHANGE responsable_id responsable_id INT UNSIGNED NOT NULL, CHANGE description description VARCHAR(45) DEFAULT NULL');
        // objet_carac.couleur VARCHAR(45)→VARCHAR(6) may truncate existing data
        $this->safeAlter('objet_carac', 'ALTER TABLE objet_carac CHANGE couleur couleur VARCHAR(6) DEFAULT NULL');
        $this->dropFk('origine_bonus', 'FK_BE693547D0F97A8');
        $this->dropIndex('origine_bonus', 'bonus_territoire');
        $this->connection->executeStatement('ALTER TABLE origine_bonus CHANGE status status VARCHAR(36) DEFAULT NULL, CHANGE creation_date creation_date DATETIME DEFAULT NULL');
        $this->addFk('origine_bonus', 'FK_BE693547D0F97A8', 'ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE693547D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->renameIndex('origine_bonus', 'idx_be69354769545666', 'fk_bonus_idx');
        $this->renameIndex('origine_bonus', 'idx_be693547d0f97a8', 'fk_territoire_idx');
        // participant.personnage_secondaire_id nullable→NOT NULL
        $this->safeAlter('participant', 'ALTER TABLE participant CHANGE personnage_secondaire_id personnage_secondaire_id INT NOT NULL');
        $this->renameIndex('participant', 'fk_joueur_gn_gn1_idx', 'IDX_D79F6B11AFC9C052');
        $this->renameIndex('participant', 'fk_joueur_gn_user1_idx', 'IDX_D79F6B11A76ED395');
        $this->renameIndex('participant', 'fk_participant_personnage_secondaire1_idx', 'IDX_D79F6B11E6917FB3');
        $this->renameIndex('participant', 'fk_participant_personnage1_idx', 'IDX_D79F6B115E315342');
        $this->renameIndex('participant', 'fk_participant_billet1_idx', 'IDX_D79F6B1144973C78');
        $this->renameIndex('participant', 'fk_participant_groupe_gn1_idx', 'IDX_D79F6B11FA640E02');
        $this->renameIndex('participant_potions_depart', 'idx_d485198a5e315343', 'IDX_E4DE0E59D1C3019');
        $this->renameIndex('participant_potions_depart', 'idx_d485198a7126b349', 'IDX_E4DE0E57126B348');
        $this->safeAlter('participant_has_restauration', 'ALTER TABLE participant_has_restauration CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // personnage: groupe_id/territoire_id/user_id nullable→NOT NULL
        $this->safeAlter('personnage', 'ALTER TABLE personnage CHANGE groupe_id groupe_id INT NOT NULL, CHANGE territoire_id territoire_id INT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_has_item CHANGE item_id item_id INT NOT NULL');
        // personnages_prieres: change PK column order
        $this->safeAlter('personnages_prieres', 'ALTER TABLE personnages_prieres DROP PRIMARY KEY, ADD PRIMARY KEY (personnage_id, priere_id)');
        // personnages_connaissances: drop id/discr columns + change PK (only if id column still exists)
        if ($this->columnExists('personnages_connaissances', 'id')) {
            $this->safeAlter('personnages_connaissances', 'ALTER TABLE personnages_connaissances MODIFY id INT UNSIGNED NOT NULL');
            $this->safeAlter('personnages_connaissances', 'ALTER TABLE personnages_connaissances DROP PRIMARY KEY, DROP id, DROP discr, CHANGE connaissance_id connaissance_id INT UNSIGNED NOT NULL, ADD PRIMARY KEY (personnage_id, connaissance_id)');
        }
        $this->renameIndex('personnages_connaissances', 'fk_personnages_connaissances', 'IDX_3F5B4C1D5E315342');
        $this->renameIndex('personnages_connaissances', 'fk_connaissances', 'IDX_3F5B4C1D68A34E8E');
        // personnage_apprentissage.competence_id nullable→NOT NULL
        $this->safeAlter('personnage_apprentissage', 'ALTER TABLE personnage_apprentissage CHANGE competence_id competence_id INT NOT NULL');
        // personnage_background.text LONGTEXT→VARCHAR(255) may truncate existing data
        $this->safeAlter('personnage_background', 'ALTER TABLE personnage_background CHANGE text text VARCHAR(255) DEFAULT NULL');
        $this->renameIndex('personnage_background', 'fk_personnage_background_gn1_idx', 'IDX_273D6F45AFC9C052');
        // Create covering indexes explicitly so the FK on bonus_id has an alternative before UNIQUE is dropped
        $this->addIndex('personnage_bonus', 'fk_bonus_idx', 'CREATE INDEX fk_bonus_idx ON personnage_bonus (bonus_id)');
        $this->addIndex('personnage_bonus', 'fk_personnage_idx', 'CREATE INDEX fk_personnage_idx ON personnage_bonus (personnage_id)');
        // Now safe to drop: FK can fall back to fk_bonus_idx
        $this->dropIndex('personnage_bonus', 'UNIQ_35CB734069545666');
        // Drop legacy-named indexes if still present on this DB
        $this->dropIndex('personnage_bonus', 'idx_35cb734069545666');
        $this->dropIndex('personnage_bonus', 'idx_35cb73405e315342');
        // personnage_bonus.personnage_id nullable→NOT NULL
        $this->safeAlter('personnage_bonus', 'ALTER TABLE personnage_bonus CHANGE personnage_id personnage_id INT NOT NULL');
        // personnage_has_question.question_id nullable→NOT NULL
        $this->safeAlter('personnage_has_question', 'ALTER TABLE personnage_has_question CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE question_id question_id INT NOT NULL');
        $this->safeAlter('personnage_has_token', 'ALTER TABLE personnage_has_token CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->safeAlter('personnage_ingredient', 'ALTER TABLE personnage_ingredient CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->safeAlter('personnage_ressource', 'ALTER TABLE personnage_ressource CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->safeAlter('personnages_chronologie', 'ALTER TABLE personnages_chronologie CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE evenement evenement VARCHAR(255) NOT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        $this->renameIndex('personnages_chronologie', 'fk_6ecc33456843', 'IDX_3F01054A5E315342');
        // personnages_lignee: parent1_id/parent2_id/lignee_id nullable→NOT NULL
        $this->safeAlter('personnages_lignee', 'ALTER TABLE personnages_lignee CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE parent1_id parent1_id INT NOT NULL, CHANGE parent2_id parent2_id INT NOT NULL, CHANGE lignee_id lignee_id INT NOT NULL, CHANGE discr discr VARCHAR(255) NOT NULL');
        $this->renameIndex('personnages_lignee', 'fk_6ecc33456847', 'IDX_E0C9F0805E315342');
        $this->renameIndex('personnages_lignee', 'fk_6ecc33456844', 'IDX_E0C9F080861B2665');
        $this->renameIndex('personnages_lignee', 'fk_6ecc33456845', 'IDX_E0C9F08094AE898B');
        $this->renameIndex('personnages_lignee', 'fk_6ecc33456846', 'IDX_E0C9F0802B3F22E1');
        $this->connection->executeStatement('ALTER TABLE photo CHANGE name name VARCHAR(45) DEFAULT NULL, CHANGE extension extension VARCHAR(45) DEFAULT NULL, CHANGE creation_date creation_date DATETIME DEFAULT NULL, CHANGE filename filename VARCHAR(100) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE priere CHANGE sphere_id sphere_id INT DEFAULT NULL');
        // pugilat_history.date DATE→DATETIME (type change)
        $this->safeAlter('pugilat_history', 'ALTER TABLE pugilat_history CHANGE date date DATETIME NOT NULL');
        $this->connection->executeStatement('ALTER TABLE qr_code_scan_log CHANGE item_id item_id INT DEFAULT NULL');
        $this->safeAlter('quality_valeur', 'ALTER TABLE quality_valeur CHANGE id id INT AUTO_INCREMENT NOT NULL');
        // question.text LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('question', 'ALTER TABLE question CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE text text VARCHAR(255) NOT NULL');
        // rangement.localisation_id nullable→NOT NULL
        $this->safeAlter('rangement', 'ALTER TABLE rangement CHANGE localisation_id localisation_id INT NOT NULL');
        $this->safeAlter('relecture', 'ALTER TABLE relecture CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT NOT NULL');
        // religion description fields LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('religion', 'ALTER TABLE religion CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE description_orga description_orga VARCHAR(255) DEFAULT NULL, CHANGE description_fervent description_fervent VARCHAR(255) DEFAULT NULL, CHANGE description_pratiquant description_pratiquant VARCHAR(255) DEFAULT NULL, CHANGE description_fanatique description_fanatique VARCHAR(255) DEFAULT NULL, CHANGE secret secret TINYINT(1) DEFAULT 0');
        $this->safeAlter('religion_description', 'ALTER TABLE religion_description CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
        // religion_level.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('religion_level', 'ALTER TABLE religion_level CHANGE description description VARCHAR(255) DEFAULT NULL');
        // renomme_history.explication LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('renomme_history', 'ALTER TABLE renomme_history CHANGE explication explication VARCHAR(255) NOT NULL');
        $this->safeAlter('reponse', 'ALTER TABLE reponse CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE question_id question_id INT NOT NULL');
        // restauration.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('restauration', 'ALTER TABLE restauration CHANGE description description VARCHAR(255) DEFAULT NULL');
        // rule.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('rule', 'ALTER TABLE rule CHANGE description description VARCHAR(255) DEFAULT NULL');
        // rumeur: territoire_id nullable→NOT NULL, text LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('rumeur', 'ALTER TABLE rumeur CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE territoire_id territoire_id INT NOT NULL, CHANGE text text VARCHAR(255) NOT NULL');
        // secondary_group_type.description NULL→NOT NULL: may fail if NULL values exist
        $this->safeAlter('secondary_group_type', 'ALTER TABLE secondary_group_type CHANGE description description LONGTEXT NOT NULL');
        // sort/sorts.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('sort', 'ALTER TABLE sort CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->safeAlter('sorts', 'ALTER TABLE sorts CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->dropFk('technologie', 'FK_technologie_competence_family');
        // technologie.description LONGTEXT→TEXT may truncate values >65535 chars
        $this->safeAlter('technologie', 'ALTER TABLE technologie CHANGE description description TEXT NOT NULL');
        // FK_AD813674F7EB2017 already added by Version20260303150041 — do not re-add
        $this->renameIndex('technologie', 'fk_technologie_competence_family', 'IDX_AD813674F7EB2017');
        $this->dropFk('technologies_ressources', 'FK_B15E3D68261A27D2');
        $this->connection->executeStatement('ALTER TABLE technologies_ressources CHANGE technologie_id technologie_id INT UNSIGNED DEFAULT NULL, CHANGE ressource_id ressource_id INT DEFAULT NULL');
        $this->addFk('technologies_ressources', 'FK_B15E3D68261A27D2', 'ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68261A27D2 FOREIGN KEY (technologie_id) REFERENCES technologie (id)');
        $this->renameIndex('technologies_ressources', 'fk_technologies_ressources_idx', 'IDX_B15E3D68261A27D2');
        $this->renameIndex('technologies_ressources', 'fk_b15e3d68fc6cd52a', 'IDX_B15E3D68FC6CD52A');
        $this->connection->executeStatement('ALTER TABLE territoire CHANGE appelation_id appelation_id INT DEFAULT NULL, CHANGE culture_id culture_id INT DEFAULT NULL');
        // Re-add FK after both culture.id and territoire.culture_id are now INT (compatible)
        $this->addFk('territoire', 'FK_B8655F54B108249D', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->renameIndex('territoire', 'fk_zone_politique_zone_politique1_idx', 'fk_territoire_territoire1_idx');
        $this->connection->executeStatement('ALTER TABLE territoire_has_loi CHANGE loi_id loi_id INT NOT NULL');
        // token.description LONGTEXT→VARCHAR(255) may truncate
        $this->safeAlter('token', 'ALTER TABLE token CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->dropFk('user', 'FK_8D93D649E6917FB3');
        // DROP is_enabled only if it still exists; password/pwd nullable→NOT NULL
        $dropIsEnabled = $this->columnExists('user', 'is_enabled') ? 'DROP is_enabled, ' : '';
        $this->safeAlter('user', "ALTER TABLE user {$dropIsEnabled}CHANGE password password VARCHAR(255) NOT NULL, CHANGE creation_date creation_date DATETIME DEFAULT NULL, CHANGE username username VARCHAR(100) NOT NULL, CHANGE timePasswordResetRequested timePasswordResetRequested INT DEFAULT NULL, CHANGE pwd pwd VARCHAR(255) NOT NULL");
        $this->addFk('user', 'FK_8D93D649E6917FB3', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage_secondaire (id)');
        $this->connection->executeStatement("ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE available_at available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    // -------------------------------------------------------------------------
    // Down
    // -------------------------------------------------------------------------

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE background CHANGE visibility visibility VARCHAR(45) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE billet CHANGE label label VARCHAR(45) NOT NULL');
        $this->renameIndex('billet', 'fk_billet_user1', 'fk_billet_user1_idx');
        $this->connection->executeStatement('ALTER TABLE bonus CHANGE json_data json_data JSON DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->connection->executeStatement('ALTER TABLE competence CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->safeAlter('competence_attribute', 'ALTER TABLE competence_attribute DROP PRIMARY KEY, ADD PRIMARY KEY (competence_id, attribute_type_id)');
        $this->connection->executeStatement('ALTER TABLE connaissance CHANGE label label VARCHAR(45) NOT NULL COLLATE `utf8mb3_unicode_ci`, CHANGE contraintes contraintes LONGTEXT DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL, CHANGE niveau niveau INT UNSIGNED DEFAULT 1 NOT NULL, CHANGE discr discr VARCHAR(45) DEFAULT \'extended\' NOT NULL COLLATE `utf8mb3_unicode_ci`');
        $this->connection->executeStatement('ALTER TABLE culture CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->dropFk('debriefing', 'FK_CB81225E99E6F5DF');
        $this->connection->executeStatement('ALTER TABLE debriefing CHANGE player_id player_id INT UNSIGNED DEFAULT NULL');
        $this->renameIndex('debriefing', 'IDX_CB81225E99E6F5DF', 'fk_debriefing_player1_idx');
        $this->connection->executeStatement('ALTER TABLE document CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE update_date update_date DATETIME NOT NULL');
        $this->connection->executeStatement('ALTER TABLE evenement CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->dropIndex('experience_usage', 'IDX_B3B922665E315342');
        $this->dropIndex('experience_usage', 'fk_experience_usage_personnage1_idx');
        $this->addIndex('experience_usage', 'fk_experience_usage_personnage1_idx', 'CREATE INDEX fk_experience_usage_personnage1_idx ON experience_usage (personnage_id)');
        $this->connection->executeStatement('ALTER TABLE formulaire CHANGE id id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE gn CHANGE date_jeu date_jeu INT UNSIGNED DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe CHANGE territoire_id territoire_id INT DEFAULT NULL, CHANGE description_membres description_membres TEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_allie CHANGE message_allie message_allie LONGTEXT DEFAULT NULL');
        $this->dropFk('groupe_gn', 'FK_413F11C8EAED05C');
        $this->dropIndex('groupe_gn', 'IDX_413F11C8EAED05C');
        $this->connection->executeStatement('ALTER TABLE groupe_gn CHANGE responsable_id responsable_id INT DEFAULT NULL, CHANGE place_available place_available INT DEFAULT NULL, CHANGE agents agents INT DEFAULT 0 NOT NULL, CHANGE bateaux bateaux INT DEFAULT 0 NOT NULL, CHANGE sieges sieges INT DEFAULT 0 NOT NULL, CHANGE initiative initiative INT DEFAULT 0 NOT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_gn_ordre CHANGE groupe_gn_id groupe_gn_id INT DEFAULT NULL, CHANGE cible_id cible_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_ingredient CHANGE groupe_id groupe_id INT NOT NULL, CHANGE ingredient_id ingredient_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_item CHANGE item_id item_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE groupe_has_ressource CHANGE groupe_id groupe_id INT NOT NULL, CHANGE ressource_id ressource_id INT NOT NULL');
        $this->dropIndex('groupe_langue', 'UNIQ_F86989B4EA750E8');
        $this->connection->executeStatement('ALTER TABLE groupe_langue CHANGE label label VARCHAR(100) NOT NULL');
        $this->connection->executeStatement('ALTER TABLE heroisme_history CHANGE personnage_id personnage_id INT NOT NULL, CHANGE date date DATE NOT NULL, CHANGE explication explication LONGTEXT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE ingredient CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE resolution resolution LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_document CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_evenement CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL, CHANGE evenement_id evenement_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_groupe CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL, CHANGE groupe_id groupe_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_groupe_secondaire CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_lieu CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_modification CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE intrigue_has_objectif CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL, CHANGE objectif_id objectif_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE item CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE quality_id quality_id INT NOT NULL, CHANGE statut_id statut_id INT DEFAULT NULL');
        $this->dropFk('langue', 'FK_9357758E7A3969FA');
        $this->dropIndex('level', 'UNIQ_9AEACC134F2FFF1C');
        $this->connection->executeStatement('ALTER TABLE lignees CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE discr discr VARCHAR(255) DEFAULT \'extended\' NOT NULL');
        $this->connection->executeStatement('ALTER TABLE localisation CHANGE `precision` `precision` VARCHAR(450) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE loi CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE message CHANGE lu lu TINYINT(1) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE notification CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->renameIndex('notification', 'IDX_BF5476CAA76ED395', 'fk_notification_user1_idx');
        $this->connection->executeStatement('ALTER TABLE objectif CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE objet CHANGE proprietaire_id proprietaire_id INT DEFAULT NULL, CHANGE responsable_id responsable_id INT UNSIGNED DEFAULT NULL, CHANGE description description VARCHAR(450) DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE objet_carac CHANGE couleur couleur VARCHAR(45) DEFAULT NULL');
        $this->dropFk('origine_bonus', 'FK_BE693547D0F97A8');
        $this->connection->executeStatement('ALTER TABLE origine_bonus CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE status status VARCHAR(32) DEFAULT NULL');
        $this->addFk('origine_bonus', 'FK_BE693547D0F97A8', 'ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE693547D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addIndex('origine_bonus', 'bonus_territoire', 'CREATE UNIQUE INDEX bonus_territoire ON origine_bonus (bonus_id, territoire_id)');
        $this->renameIndex('origine_bonus', 'fk_bonus_idx', 'IDX_BE69354769545666');
        $this->renameIndex('origine_bonus', 'fk_territoire_idx', 'IDX_BE693547D0F97A8');
        $this->connection->executeStatement('ALTER TABLE participant CHANGE personnage_secondaire_id personnage_secondaire_id INT DEFAULT NULL');
        $this->renameIndex('participant', 'IDX_D79F6B11AFC9C052', 'fk_joueur_gn_gn1_idx');
        $this->renameIndex('participant', 'IDX_D79F6B11A76ED395', 'fk_joueur_gn_user1_idx');
        $this->renameIndex('participant', 'IDX_D79F6B11E6917FB3', 'fk_participant_personnage_secondaire1_idx');
        $this->renameIndex('participant', 'IDX_D79F6B115E315342', 'fk_participant_personnage1_idx');
        $this->renameIndex('participant', 'IDX_D79F6B1144973C78', 'fk_participant_billet1_idx');
        $this->renameIndex('participant', 'IDX_D79F6B11FA640E02', 'fk_participant_groupe_gn1_idx');
        $this->connection->executeStatement('ALTER TABLE participant_has_restauration CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->renameIndex('participant_potions_depart', 'IDX_E4DE0E59D1C3019', 'IDX_D485198A5E315343');
        $this->renameIndex('participant_potions_depart', 'IDX_E4DE0E57126B348', 'IDX_D485198A7126B349');
        $this->connection->executeStatement('ALTER TABLE personnage CHANGE groupe_id groupe_id INT DEFAULT NULL, CHANGE territoire_id territoire_id INT DEFAULT NULL, CHANGE user_id user_id INT UNSIGNED DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_apprentissage CHANGE competence_id competence_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_background CHANGE text text LONGTEXT DEFAULT NULL');
        $this->renameIndex('personnage_background', 'IDX_273D6F45AFC9C052', 'fk_personnage_background_gn1_idx');
        $this->connection->executeStatement('ALTER TABLE personnage_bonus CHANGE personnage_id personnage_id INT DEFAULT NULL');
        $this->addIndex('personnage_bonus', 'UNIQ_35CB734069545666', 'CREATE UNIQUE INDEX UNIQ_35CB734069545666 ON personnage_bonus (bonus_id, personnage_id)');
        $this->renameIndex('personnage_bonus', 'fk_personnage_idx', 'IDX_35CB73405E315342');
        $this->renameIndex('personnage_bonus', 'fk_bonus_idx', 'IDX_35CB734069545666');
        $this->connection->executeStatement('ALTER TABLE personnage_has_item CHANGE item_id item_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_has_question CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE question_id question_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_has_token CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_ingredient CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnage_ressource CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE personnages_chronologie CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE evenement evenement VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, CHANGE discr discr VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT \'extended\' NOT NULL COLLATE `utf8mb3_unicode_ci`');
        $this->renameIndex('personnages_chronologie', 'IDX_3F01054A5E315342', 'FK_6ECC33456843');
        // personnages_connaissances: restore id/discr columns (only if id doesn't already exist)
        if (!$this->columnExists('personnages_connaissances', 'id')) {
            $this->safeAlter('personnages_connaissances', 'ALTER TABLE personnages_connaissances ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL, ADD discr VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT \'extended\' NOT NULL COLLATE `utf8mb3_unicode_ci`, CHANGE connaissance_id connaissance_id INT UNSIGNED DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        }
        $this->renameIndex('personnages_connaissances', 'IDX_3F5B4C1D5E315342', 'FK_PERSONNAGES_CONNAISSANCES');
        $this->renameIndex('personnages_connaissances', 'IDX_3F5B4C1D68A34E8E', 'FK_CONNAISSANCES');
        $this->connection->executeStatement('ALTER TABLE personnages_lignee CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE parent1_id parent1_id INT DEFAULT NULL, CHANGE parent2_id parent2_id INT DEFAULT NULL, CHANGE lignee_id lignee_id INT UNSIGNED DEFAULT NULL, CHANGE discr discr VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT \'extended\' NOT NULL COLLATE `utf8mb3_unicode_ci`');
        $this->renameIndex('personnages_lignee', 'IDX_E0C9F0805E315342', 'FK_6ECC33456847');
        $this->renameIndex('personnages_lignee', 'IDX_E0C9F080861B2665', 'FK_6ECC33456844');
        $this->renameIndex('personnages_lignee', 'IDX_E0C9F08094AE898B', 'FK_6ECC33456845');
        $this->renameIndex('personnages_lignee', 'IDX_E0C9F0802B3F22E1', 'FK_6ECC33456846');
        $this->safeAlter('personnages_prieres', 'ALTER TABLE personnages_prieres DROP PRIMARY KEY, ADD PRIMARY KEY (priere_id, personnage_id)');
        $this->connection->executeStatement('ALTER TABLE photo CHANGE name name VARCHAR(45) NOT NULL, CHANGE extension extension VARCHAR(45) NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE filename filename VARCHAR(100) NOT NULL');
        $this->connection->executeStatement('ALTER TABLE priere CHANGE sphere_id sphere_id INT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE pugilat_history CHANGE date date DATE NOT NULL');
        $this->connection->executeStatement('ALTER TABLE qr_code_scan_log CHANGE item_id item_id INT UNSIGNED DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE quality_valeur CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE question CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE text text LONGTEXT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE rangement CHANGE localisation_id localisation_id INT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE relecture CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE intrigue_id intrigue_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE religion CHANGE description description LONGTEXT DEFAULT NULL, CHANGE description_orga description_orga LONGTEXT DEFAULT NULL, CHANGE description_fervent description_fervent LONGTEXT DEFAULT NULL, CHANGE description_pratiquant description_pratiquant LONGTEXT DEFAULT NULL, CHANGE description_fanatique description_fanatique LONGTEXT DEFAULT NULL, CHANGE secret secret TINYINT(1) DEFAULT 0 NOT NULL');
        $this->connection->executeStatement('ALTER TABLE religion_description CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE religion_level CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE renomme_history CHANGE explication explication LONGTEXT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE reponse CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE question_id question_id INT UNSIGNED NOT NULL');
        $this->connection->executeStatement('ALTER TABLE restauration CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE rule CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE rumeur CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE territoire_id territoire_id INT DEFAULT NULL, CHANGE text text LONGTEXT NOT NULL');
        $this->connection->executeStatement('ALTER TABLE secondary_group_type CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE sort CHANGE description description LONGTEXT DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE sorts CHANGE description description LONGTEXT DEFAULT NULL');
        // FK_AD813674F7EB2017 was added by Version20260303150041, not this migration — do not drop it here
        $this->connection->executeStatement('ALTER TABLE technologie CHANGE description description LONGTEXT NOT NULL');
        $this->addFk('technologie', 'FK_technologie_competence_family', 'ALTER TABLE technologie ADD CONSTRAINT FK_technologie_competence_family FOREIGN KEY (competence_family_id) REFERENCES competence_family (id) ON DELETE SET NULL');
        $this->renameIndex('technologie', 'IDX_AD813674F7EB2017', 'FK_technologie_competence_family');
        $this->dropFk('technologies_ressources', 'FK_B15E3D68261A27D2');
        $this->connection->executeStatement('ALTER TABLE technologies_ressources CHANGE technologie_id technologie_id INT UNSIGNED NOT NULL, CHANGE ressource_id ressource_id INT NOT NULL');
        $this->addFk('technologies_ressources', 'FK_B15E3D68261A27D2', 'ALTER TABLE technologies_ressources ADD CONSTRAINT FK_B15E3D68261A27D2 FOREIGN KEY (technologie_id) REFERENCES technologie (id) ON DELETE CASCADE');
        $this->renameIndex('technologies_ressources', 'IDX_B15E3D68261A27D2', 'fk_technologies_ressources_idx');
        $this->renameIndex('technologies_ressources', 'IDX_B15E3D68FC6CD52A', 'FK_B15E3D68FC6CD52A');
        $this->dropFk('territoire', 'FK_B8655F54B108249D');
        $this->connection->executeStatement('ALTER TABLE territoire CHANGE appelation_id appelation_id INT NOT NULL, CHANGE culture_id culture_id INT UNSIGNED DEFAULT NULL');
        $this->addFk('territoire', 'FK_B8655F54B108249D', 'ALTER TABLE territoire ADD CONSTRAINT FK_B8655F54B108249D FOREIGN KEY (culture_id) REFERENCES culture (id)');
        $this->renameIndex('territoire', 'fk_territoire_territoire1_idx', 'fk_zone_politique_zone_politique1_idx');
        $this->connection->executeStatement('ALTER TABLE territoire_has_loi CHANGE loi_id loi_id INT UNSIGNED NOT NULL');
        // territoire_quete FKs added by Version20260303150041, not this migration — do not drop them here
        $this->connection->executeStatement('ALTER TABLE token CHANGE description description LONGTEXT DEFAULT NULL');
        $this->dropFk('user', 'FK_8D93D649E6917FB3');
        // ADD is_enabled only if it doesn't already exist
        $addIsEnabled = !$this->columnExists('user', 'is_enabled') ? 'ADD is_enabled TINYINT(1) DEFAULT NULL, ' : '';
        $this->connection->executeStatement("ALTER TABLE user {$addIsEnabled}CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE pwd pwd VARCHAR(255) DEFAULT NULL, CHANGE username username VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE timePasswordResetRequested timePasswordResetRequested INT UNSIGNED DEFAULT NULL");
        $this->addFk('user', 'FK_8D93D649E6917FB3', 'ALTER TABLE user ADD CONSTRAINT FK_8D93D649E6917FB3 FOREIGN KEY (personnage_secondaire_id) REFERENCES personnage (id)');
    }
}
