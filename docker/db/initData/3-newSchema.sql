-- Schema delta: changes required after 1-initSchema.sql to align with current Doctrine entity state.
-- Applied in docker-entrypoint-initdb.d order (after 1-initSchema.sql and 2-insertAnonData.sql).

SET FOREIGN_KEY_CHECKS=0;

-- =============================================================================
-- 1. DROP legacy tables (forum system, obsolete trackers)
--    These were present in the Silex-era schema but removed via Symfony migrations.
-- =============================================================================

DROP TABLE IF EXISTS `wt_heroisme_ad`;
DROP TABLE IF EXISTS `wt_litterature_top`;
DROP TABLE IF EXISTS `item_bak`;
DROP TABLE IF EXISTS `watching_user`;
DROP TABLE IF EXISTS `post_view`;
DROP TABLE IF EXISTS `post`;
DROP TABLE IF EXISTS `topic`;

-- =============================================================================
-- 2. DROP topic_id columns from tables that referenced the old forum system
-- =============================================================================

-- gn
ALTER TABLE `gn` DROP FOREIGN KEY `FK_C16FA3C01F55203D`;
DROP INDEX `fk_gn_topic1_idx` ON `gn`;
ALTER TABLE `gn` DROP COLUMN `topic_id`;

-- groupe (only index, no FK constraint in initSchema)
DROP INDEX `fk_groupe_topic1_idx` ON `groupe`;
ALTER TABLE `groupe` DROP COLUMN `topic_id`;

-- religion
ALTER TABLE `religion` DROP FOREIGN KEY `FK_1055F4F91F55203D`;
DROP INDEX `fk_religion_topic1_idx` ON `religion`;
ALTER TABLE `religion` DROP COLUMN `topic_id`;

-- secondary_group
ALTER TABLE `secondary_group` DROP FOREIGN KEY `FK_717A91A31F55203D`;
DROP INDEX `fk_secondary_group_topic1_idx` ON `secondary_group`;
ALTER TABLE `secondary_group` DROP COLUMN `topic_id`;

-- territoire (no FK constraint for topic_id in initSchema)
ALTER TABLE `territoire` DROP COLUMN `topic_id`;

SET FOREIGN_KEY_CHECKS=1;

-- =============================================================================
-- 3. CREATE new tables added after the initSchema export
-- =============================================================================

CREATE TABLE IF NOT EXISTS `groupe_gn_ordre` (
    `id`           INT AUTO_INCREMENT NOT NULL,
    `groupe_gn_id` INT          DEFAULT NULL,
    `cible_id`     INT          DEFAULT NULL,
    `ordre`        VARCHAR(255) NOT NULL,
    `extra`        VARCHAR(255) NOT NULL,
    `discr`        VARCHAR(255) NOT NULL,
    INDEX `IDX_BDA495F5FA640E02` (`groupe_gn_id`),
    INDEX `IDX_BDA495F5A96E5E09` (`cible_id`),
    PRIMARY KEY (`id`),
    CONSTRAINT `FK_BDA495F5FA640E02` FOREIGN KEY (`groupe_gn_id`) REFERENCES `groupe_gn` (`id`),
    CONSTRAINT `FK_BDA495F5A96E5E09` FOREIGN KEY (`cible_id`)     REFERENCES `territoire` (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- =============================================================================
-- 4. Mark all Doctrine migrations as executed
--    (schema is already at current state — no migrations should run on first boot)
-- =============================================================================

INSERT IGNORE INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
    ('DoctrineMigrations\\Version20231021124931', NOW(), 0),
    ('DoctrineMigrations\\Version20250215141604', NOW(), 0),
    ('DoctrineMigrations\\Version20250216202635', NOW(), 0),
    ('DoctrineMigrations\\Version20250221215610', NOW(), 0),
    ('DoctrineMigrations\\Version20250224083644', NOW(), 0),
    ('DoctrineMigrations\\Version20250228213836', NOW(), 0),
    ('DoctrineMigrations\\Version20250325074112', NOW(), 0),
    ('DoctrineMigrations\\Version20250407060810', NOW(), 0),
    ('DoctrineMigrations\\Version20250414202703', NOW(), 0),
    ('DoctrineMigrations\\Version20250415174133', NOW(), 0),
    ('DoctrineMigrations\\Version20250415205211', NOW(), 0),
    ('DoctrineMigrations\\Version20250419213551', NOW(), 0),
    ('DoctrineMigrations\\Version20250507205717', NOW(), 0),
    ('DoctrineMigrations\\Version20250510113837', NOW(), 0),
    ('DoctrineMigrations\\Version20250510143417', NOW(), 0),
    ('DoctrineMigrations\\Version20250510163335', NOW(), 0),
    ('DoctrineMigrations\\Version20250528230133', NOW(), 0),
    ('DoctrineMigrations\\Version20250607132413', NOW(), 0),
    ('DoctrineMigrations\\Version20250729183439', NOW(), 0),
    ('DoctrineMigrations\\Version20251205215403', NOW(), 0),
    ('DoctrineMigrations\\Version20251213203314', NOW(), 0),
    ('DoctrineMigrations\\Version20260303150041', NOW(), 0);
