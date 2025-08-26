SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE IF NOT EXISTS `age` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bonus` int DEFAULT NULL,
  `enableCreation` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `minimumValue` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `annonce` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `archive` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_annonce_gn1_idx` (`gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `appelation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appelation_id` int DEFAULT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `titre` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_territoire_denomination_territoire_denomination1_idx` (`appelation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `attribute_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `background` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int DEFAULT NULL,
  `titre` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_background_groupe1_idx` (`groupe_id`),
  KEY `fk_background_user1_idx` (`user_id`),
  KEY `fk_background_gn1_idx` (`gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `billet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `createur_id` int UNSIGNED NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  `fedegn` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_billet_user1_idx` (`createur_id`),
  KEY `fk_billet_gn1_idx` (`gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valeur` int DEFAULT NULL,
  `periode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `competence_id` int DEFAULT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT '(DC2Type:json)',
  `discr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_competence_idx` (`competence_id`)
) ;

CREATE TABLE IF NOT EXISTS `chronologie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zone_politique_id` int NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `month` int DEFAULT NULL,
  `day` int DEFAULT NULL,
  `visibilite` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_chronologie_zone_politique1_idx` (`zone_politique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label_masculin` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `label_feminin` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `image_m` varchar(90) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `image_f` varchar(90) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `classe_competence_family_creation` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_4FC70A4B8F5EA509` (`classe_id`),
  KEY `IDX_4FC70A4BF7EB2017` (`competence_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `classe_competence_family_favorite` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_70EC01E68F5EA509` (`classe_id`),
  KEY `IDX_70EC01E6F7EB2017` (`competence_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `classe_competence_family_normale` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_D65491848F5EA509` (`classe_id`),
  KEY `IDX_D6549184F7EB2017` (`competence_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `competence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `competence_family_id` int NOT NULL,
  `level_id` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `materiel` longtext COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_competence_niveau_competence1_idx` (`competence_family_id`),
  KEY `fk_competence_niveau_niveau1_idx` (`level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `competence_attribute` (
  `competence_id` int NOT NULL,
  `attribute_type_id` int NOT NULL,
  `value` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`competence_id`,`attribute_type_id`),
  KEY `fk_competence_has_attribute_type_attribute_type1_idx` (`attribute_type_id`),
  KEY `fk_competence_has_attribute_type_competence1_idx` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `competence_family` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `connaissance` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext,
  `contraintes` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int UNSIGNED NOT NULL DEFAULT '1',
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `discr` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `construction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `defense` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `culture` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_complete` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `debriefing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `gn_id` int DEFAULT NULL,
  `titre` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `player_id` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_debriefing_groupe1_idx` (`groupe_id`),
  KEY `fk_debriefing_user1_idx` (`user_id`),
  KEY `fk_debriefing_gn1_idx` (`gn_id`),
  KEY `fk_debriefing_player1_idx` (`player_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `document` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `code` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `titre` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cryptage` tinyint(1) NOT NULL,
  `statut` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `auteur` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `impression` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_document_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_has_langue` (
  `document_id` int NOT NULL,
  `langue_id` int NOT NULL,
  PRIMARY KEY (`document_id`,`langue_id`),
  KEY `IDX_1EB6C943C33F7837` (`document_id`),
  KEY `IDX_1EB6C9432AADBACD` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `domaine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `espece` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret` tinyint(1) NOT NULL,
  `description_secrete` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `espece_personnage` (
  `espece_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`espece_id`,`personnage_id`),
  KEY `IDX_D7C6D24D2D191E7A` (`espece_id`),
  KEY `IDX_D7C6D24D5E315342` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `etat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `etat_civil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prenom` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prenom_usage` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `telephone` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `photo` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `date_naissance` datetime DEFAULT NULL,
  `probleme_medicaux` longtext COLLATE utf8mb3_unicode_ci,
  `personne_a_prevenir` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tel_pap` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fedegn` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `evenement` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `text` varchar(450) COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_gain` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `explanation` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `operation_date` datetime NOT NULL,
  `xp_gain` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experience_gain_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `competence_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `operation_date` datetime NOT NULL,
  `xp_use` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experience_usage_competence1_idx` (`competence_id`),
  KEY `fk_experience_usage_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `formulaire` (
  `id` int NOT NULL,
  `title` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `genre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `gn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `xp_creation` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `date_installation_joueur` datetime DEFAULT NULL,
  `date_fin_orga` datetime DEFAULT NULL,
  `adresse` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `billetterie` longtext COLLATE utf8mb3_unicode_ci,
  `conditions_inscription` longtext COLLATE utf8mb3_unicode_ci,
  `date_jeu` int UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_gn_topic1_idx` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scenariste_id` int UNSIGNED DEFAULT NULL,
  `responsable_id` int UNSIGNED DEFAULT NULL,
  `topic_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `code` varchar(10) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `jeu_maritime` tinyint(1) DEFAULT NULL,
  `jeu_strategique` tinyint(1) DEFAULT NULL,
  `classe_open` int DEFAULT NULL,
  `pj` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `materiel` longtext COLLATE utf8mb3_unicode_ci,
  `lock` tinyint(1) NOT NULL,
  `territoire_id` int DEFAULT NULL,
  `richesse` int DEFAULT NULL,
  `discord` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_membres` text COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_users1_idx` (`scenariste_id`),
  KEY `fk_groupe_user2_idx` (`responsable_id`),
  KEY `fk_groupe_topic1_idx` (`topic_id`),
  KEY `fk_groupe_territoire1_idx` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_allie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `groupe_allie_id` int NOT NULL,
  `groupe_accepted` tinyint(1) NOT NULL,
  `groupe_allie_accepted` tinyint(1) NOT NULL,
  `message` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message_allie` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_allie_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_allie_groupe2_idx` (`groupe_allie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `bonus_id` int NOT NULL,
  `creation_date` datetime NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_bonus_idx` (`bonus_id`),
  KEY `fk_groupe_idx` (`groupe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `classe_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_classe_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_classe_classe1_idx` (`classe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_enemy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `groupe_enemy_id` int NOT NULL,
  `groupe_peace` tinyint(1) NOT NULL,
  `groupe_enemy_peace` tinyint(1) NOT NULL,
  `message` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message_enemy` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_enemy_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_enemy_groupe2_idx` (`groupe_enemy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_gn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `gn_id` int NOT NULL,
  `responsable_id` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `free` tinyint(1) NOT NULL,
  `code` varchar(10) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `jeu_maritime` tinyint(1) DEFAULT NULL,
  `jeu_strategique` tinyint(1) DEFAULT NULL,
  `place_available` int DEFAULT NULL,
  `agents` int NOT NULL DEFAULT '0',
  `bateaux` int NOT NULL DEFAULT '0',
  `sieges` int NOT NULL DEFAULT '0',
  `initiative` int NOT NULL DEFAULT '0',
  `suzerin_id` int DEFAULT NULL,
  `connetable_id` int DEFAULT NULL,
  `intendant_id` int DEFAULT NULL,
  `navigateur_id` int DEFAULT NULL,
  `camarilla_id` int DEFAULT NULL,
  `bateaux_localisation` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `diplomate_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_gn_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_gn_gn1_idx` (`gn_id`),
  KEY `fk_groupe_gn_participant1_idx` (`responsable_id`),
  KEY `IDX_413F11C30EF1B89` (`suzerin_id`),
  KEY `IDX_413F11CB42EB948` (`connetable_id`),
  KEY `IDX_413F11C7B52884` (`intendant_id`),
  KEY `IDX_413F11C7EAA3A12` (`navigateur_id`),
  KEY `IDX_413F11C13776DBA` (`camarilla_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_has_document` (
  `groupe_id` int NOT NULL,
  `Document_id` int NOT NULL,
  PRIMARY KEY (`groupe_id`,`Document_id`),
  KEY `IDX_B55C42867A45358C` (`groupe_id`),
  KEY `IDX_B55C4286C33F7837` (`Document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_has_ingredient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_has_ingredient_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_has_ingredient_ingredient1_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_has_item` (
  `groupe_id` int NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`groupe_id`,`item_id`),
  KEY `IDX_D3E5F5317A45358C` (`groupe_id`),
  KEY `IDX_D3E5F531126F525E` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_has_ressource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_has_ressource_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_has_ressource_ressource1_idx` (`ressource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `groupe_langue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `couleur` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `heroisme_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `date` date NOT NULL,
  `heroisme` int NOT NULL,
  `explication` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_heroisme_history_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `ingredient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `niveau` int NOT NULL,
  `dose` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `titre` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `resolution` longtext COLLATE utf8mb3_unicode_ci,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `state` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_document` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `document_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_document_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_document_document1_idx` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_evenement` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `evenement_id` int UNSIGNED NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_evenement_evenement1_idx` (`evenement_id`),
  KEY `fk_intrigue_has_evenement_intrigue1_idx` (`intrigue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_groupe` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `groupe_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_groupe_groupe1_idx` (`groupe_id`),
  KEY `fk_intrigue_has_groupe_intrigue1_idx` (`intrigue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_groupe_secondaire` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `secondary_group_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_groupe_secondaire_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_groupe_secondaire_secondary_group1_idx` (`secondary_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_lieu` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `lieu_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_lieu_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_lieu_lieu1_idx` (`lieu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_modification` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_modification_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_modification_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `intrigue_has_objectif` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `intrigue_id` int UNSIGNED NOT NULL,
  `objectif_id` int UNSIGNED NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_objectif_objectif1_idx` (`objectif_id`),
  KEY `fk_intrigue_has_objectif_intrigue1_idx` (`intrigue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `item` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `identification` varchar(2) COLLATE utf8mb3_unicode_ci NOT NULL,
  `special` longtext COLLATE utf8mb3_unicode_ci,
  `couleur` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `objet_id` int NOT NULL,
  `quantite` int NOT NULL,
  `description_secrete` longtext COLLATE utf8mb3_unicode_ci,
  `description_scenariste` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_item_statut1_idx` (`statut_id`),
  KEY `fk_item_qualite1_idx` (`quality_id`),
  KEY `fk_item_objet1_idx` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_bak` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `identification` int NOT NULL,
  `special` longtext COLLATE utf8mb3_unicode_ci,
  `couleur` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `objet_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_item_statut1_idx` (`statut_id`),
  KEY `fk_item_qualite1_idx` (`quality_id`),
  KEY `fk_item_objet1_idx` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `langue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `diffusion` int DEFAULT NULL,
  `groupe_langue_id` int NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupe_langue_id_idx` (`groupe_langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `index` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `cout` int DEFAULT NULL,
  `cout_favori` int DEFAULT NULL,
  `cout_meconu` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `lieu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `lieu_has_document` (
  `document_id` int NOT NULL,
  `lieu_id` int NOT NULL,
  PRIMARY KEY (`lieu_id`,`document_id`),
  KEY `IDX_487D3F92C33F7837` (`document_id`),
  KEY `IDX_487D3F926AB213CC` (`lieu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `lignees` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `localisation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `precision` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `log_action` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED DEFAULT NULL,
  `data` json DEFAULT NULL COMMENT '(DC2Type:json)',
  `date` datetime NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5236DF30A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loi` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `membre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `secondary_group_id` int NOT NULL,
  `secret` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `private` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_groupe_secondaire_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_groupe_secondaire_secondary_group1_idx` (`secondary_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `merveille` (
  `id` int NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `bonus_id` int DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `description_scenariste` longtext COLLATE utf8mb4_unicode_ci,
  `description_cartographe` longtext COLLATE utf8mb4_unicode_ci,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `date_destruction` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D70330D0D0F97A8` (`territoire_id`),
  KEY `IDX_D70330D069545666` (`bonus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `auteur` int UNSIGNED NOT NULL,
  `destinataire` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `lu` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_message_user1_idx` (`auteur`),
  KEY `fk_message_user2_idx` (`destinataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `monnaie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime DEFAULT NULL,
  `url` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notification_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `objectif` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `text` varchar(450) COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `objet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `etat_id` int DEFAULT NULL,
  `proprietaire_id` int DEFAULT NULL,
  `responsable_id` int UNSIGNED DEFAULT NULL,
  `photo_id` int DEFAULT NULL,
  `rangement_id` int NOT NULL,
  `numero` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `nombre` int DEFAULT NULL,
  `cout` double DEFAULT NULL,
  `budget` double DEFAULT NULL,
  `investissement` tinyint(1) DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_objet_etat1_idx` (`etat_id`),
  KEY `fk_objet_possesseur1_idx` (`proprietaire_id`),
  KEY `fk_objet_users1_idx` (`responsable_id`),
  KEY `fk_objet_photo1_idx` (`photo_id`),
  KEY `fk_objet_rangement1_idx` (`rangement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `objet_carac` (
  `id` int NOT NULL AUTO_INCREMENT,
  `objet_id` int NOT NULL,
  `taille` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `poid` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `couleur` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_objet_carac_objet1_idx` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `objet_tag` (
  `objet_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`objet_id`,`tag_id`),
  KEY `IDX_E3164735F520CF5A` (`objet_id`),
  KEY `IDX_E3164735BAD26311` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `origine_bonus` (
  `bonus_id` int NOT NULL,
  `territoire_id` int NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bonus_territoire` (`bonus_id`,`territoire_id`),
  KEY `IDX_BE69354769545666` (`bonus_id`),
  KEY `IDX_BE693547D0F97A8` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `subscription_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `personnage_secondaire_id` int DEFAULT NULL,
  `personnage_id` int DEFAULT NULL,
  `billet_id` int DEFAULT NULL,
  `billet_date` datetime DEFAULT NULL,
  `groupe_gn_id` int DEFAULT NULL,
  `valide_ci_le` datetime DEFAULT NULL,
  `couchage` varchar(32) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `special` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_joueur_gn_gn1_idx` (`gn_id`),
  KEY `fk_joueur_gn_user1_idx` (`user_id`),
  KEY `fk_participant_personnage_secondaire1_idx` (`personnage_secondaire_id`),
  KEY `fk_participant_personnage1_idx` (`personnage_id`),
  KEY `fk_participant_billet1_idx` (`billet_id`),
  KEY `fk_participant_groupe_gn1_idx` (`groupe_gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `participant_has_restauration` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_id` int NOT NULL,
  `restauration_id` int NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_participant_has_restauration_participant1_idx` (`participant_id`),
  KEY `fk_participant_has_restauration_restauration1_idx` (`restauration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `participant_potions_depart` (
  `participant_id` int NOT NULL,
  `potion_id` int NOT NULL,
  PRIMARY KEY (`participant_id`,`potion_id`),
  KEY `IDX_D485198A5E315343` (`participant_id`),
  KEY `IDX_D485198A7126B349` (`potion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int DEFAULT NULL,
  `classe_id` int NOT NULL,
  `age_id` int NOT NULL,
  `genre_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `surnom` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `intrigue` tinyint(1) DEFAULT NULL,
  `renomme` int DEFAULT NULL,
  `photo` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `xp` int DEFAULT NULL,
  `territoire_id` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `materiel` longtext COLLATE utf8mb3_unicode_ci,
  `vivant` tinyint(1) NOT NULL,
  `age_reel` int DEFAULT NULL,
  `trombineUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `richesse` int DEFAULT NULL,
  `heroisme` int DEFAULT NULL,
  `sensible` tinyint(1) DEFAULT NULL,
  `bracelet` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_groupe1_idx` (`groupe_id`),
  KEY `fk_personnage_archetype1_idx` (`classe_id`),
  KEY `fk_personnage_age1_idx` (`age_id`),
  KEY `fk_personnage_genre1_idx` (`genre_id`),
  KEY `fk_personnage_territoire1_idx` (`territoire_id`),
  KEY `fk_personnage_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_chronologie` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `evenement` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `annee` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_6ECC33456843` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `personnages_competences` (
  `competence_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`competence_id`,`personnage_id`),
  KEY `IDX_8AED412315761DAB` (`competence_id`),
  KEY `IDX_8AED41235E315342` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_connaissances` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `connaissance_id` int UNSIGNED DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_PERSONNAGES_CONNAISSANCES` (`personnage_id`),
  KEY `FK_CONNAISSANCES` (`connaissance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `personnages_domaines` (
  `personnage_id` int NOT NULL,
  `domaine_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`domaine_id`),
  KEY `IDX_C31CED645E315342` (`personnage_id`),
  KEY `IDX_C31CED644272FC9F` (`domaine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_lignee` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `parent1_id` int DEFAULT NULL,
  `parent2_id` int DEFAULT NULL,
  `lignee_id` int UNSIGNED DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_6ECC33456847` (`personnage_id`),
  KEY `FK_6ECC33456844` (`parent1_id`),
  KEY `FK_6ECC33456845` (`parent2_id`),
  KEY `FK_6ECC33456846` (`lignee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `personnages_potions` (
  `personnage_id` int NOT NULL,
  `potion_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`potion_id`),
  KEY `IDX_D485198A5E315342` (`personnage_id`),
  KEY `IDX_D485198A7126B348` (`potion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_prieres` (
  `priere_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`priere_id`,`personnage_id`),
  KEY `IDX_4E610DACA8227EF5` (`priere_id`),
  KEY `IDX_4E610DAC5E315342` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_religions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `religion_id` int NOT NULL,
  `religion_level_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_religion_religion1_idx` (`religion_id`),
  KEY `fk_personnage_religion_religion_level1_idx` (`religion_level_id`),
  KEY `fk_personnages_religions_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnages_sorts` (
  `personnage_id` int NOT NULL,
  `sort_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`sort_id`),
  KEY `IDX_8ABC9FD75E315342` (`personnage_id`),
  KEY `IDX_8ABC9FD747013001` (`sort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_apprentissage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int DEFAULT NULL,
  `enseignant_id` int NOT NULL,
  `date_enseignement` int DEFAULT NULL,
  `date_usage` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `competence_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EA259B515E315342` (`personnage_id`),
  KEY `IDX_EA259B51E455FCC0` (`enseignant_id`),
  KEY `IDX_EA259B5115761DAB` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_background` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_background_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_background_user1_idx` (`user_id`),
  KEY `fk_personnage_background_gn1_idx` (`gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int DEFAULT NULL,
  `bonus_id` int NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `status` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_35CB734069545666` (`bonus_id`,`personnage_id`) USING BTREE,
  KEY `IDX_35CB73405E315342` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_has_document` (
  `personnage_id` int NOT NULL,
  `Document_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`Document_id`),
  KEY `IDX_EFBB065F5E315342` (`personnage_id`),
  KEY `IDX_EFBB065F45A3F7E0` (`Document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_has_item` (
  `personnage_id` int NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`personnage_id`,`item_id`),
  KEY `IDX_356CD1EF5E315342` (`personnage_id`),
  KEY `IDX_356CD1EF126F525E` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_has_question` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `question_id` int UNSIGNED NOT NULL,
  `reponse` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_has_question_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_has_question_question1_idx` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_has_technologie` (
  `personnage_id` int NOT NULL,
  `technologie_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`personnage_id`,`technologie_id`),
  KEY `IDX_65F62F935E315342` (`personnage_id`),
  KEY `IDX_65F62F93261A27D2` (`technologie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_has_token` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `token_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_95A714455E315342` (`personnage_id`),
  KEY `fk_personnage_has_token_token1_idx` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_ingredient` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `nombre` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F0FAA3655E315342` (`personnage_id`),
  KEY `fk_personnage_ingredient_ingredient1_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_langues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `langue_id` int NOT NULL,
  `source` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_langues_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_langues_langue1_idx` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_religion_description` (
  `personnage_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`religion_id`),
  KEY `IDX_874677E25E315342` (`personnage_id`),
  KEY `IDX_874677E2B7850CBD` (`religion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_ressource` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  `nombre` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A286E0845E315342` (`personnage_id`),
  KEY `fk_personnage_ressource_ressource1_idx` (`ressource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_secondaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `classe_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_classe1_idx` (`classe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_secondaires_competences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaires_competences_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaires_competences_competence1_idx` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_secondaires_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_skills_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaire_skills_competence1_idx` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_secondaire_competence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_competences_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaire_competences_competence1_idx` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `personnage_trigger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `tag` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `done` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trigger_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `extension` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `real_name` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` longblob,
  `creation_date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `filename` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `post` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `post_id` int DEFAULT NULL,
  `title` varchar(450) COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_post_topic1_idx` (`topic_id`),
  KEY `fk_post_user1_idx` (`user_id`),
  KEY `fk_post_post1_idx` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `postulant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `secondary_group_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `date` datetime DEFAULT NULL,
  `explanation` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `waiting` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_postulant_secondary_group1_idx` (`secondary_group_id`),
  KEY `fk_postulant_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_view` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_post_view_post1_idx` (`post_id`),
  KEY `fk_post_view_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `potion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `niveau` int NOT NULL,
  `numero` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `priere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `annonce` longtext COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `sphere_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_priere_sphere1_idx` (`sphere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `proprietaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `adresse` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mail` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tel` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `pugilat_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `date` date NOT NULL,
  `pugilat` int NOT NULL,
  `explication` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pugilat_history_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `qr_code_scan_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `participant_id` int DEFAULT NULL,
  `item_id` int UNSIGNED DEFAULT NULL,
  `date` datetime NOT NULL,
  `allowed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4F1CE7B0A76ED395` (`user_id`),
  KEY `IDX_4F1CE7B09D1C3019` (`participant_id`),
  KEY `IDX_4F1CE7B0126F525E` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `numero` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `quality_valeur` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `monnaie_id` int NOT NULL,
  `nombre` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quality_valeur_qualite1_idx` (`quality_id`),
  KEY `fk_quality_valeur_monnaie1_idx` (`monnaie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `question` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `choix` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_question_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `rangement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `localisation_id` int DEFAULT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `precision` varchar(450) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rangement_localisation1_idx` (`localisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `rarete` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `relecture` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `intrigue_id` int UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `statut` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `remarque` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_relecture_user1_idx` (`user_id`),
  KEY `fk_relecture_intrigue1_idx` (`intrigue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `religion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `blason` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_orga` longtext COLLATE utf8mb3_unicode_ci,
  `description_fervent` longtext COLLATE utf8mb3_unicode_ci,
  `description_pratiquant` longtext COLLATE utf8mb3_unicode_ci,
  `description_fanatique` longtext COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_religion_topic1_idx` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `religions_spheres` (
  `sphere_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`sphere_id`,`religion_id`),
  KEY `IDX_65855EBE75FD4EF9` (`sphere_id`),
  KEY `IDX_65855EBEB7850CBD` (`religion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `religion_description` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `religion_id` int NOT NULL,
  `religion_level_id` int NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_religion_description_religion1_idx` (`religion_id`),
  KEY `fk_religion_description_religion_level1_idx` (`religion_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `religion_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `index` int NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `renomme_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `renomme` int NOT NULL,
  `explication` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_renomme_history_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `reponse` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_id` int NOT NULL,
  `question_id` int UNSIGNED NOT NULL,
  `reponse` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reponse_participant1_idx` (`participant_id`),
  KEY `fk_reponse_idx` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `ressource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rarete_id` int NOT NULL,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ressource_rarete1_idx` (`rarete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `restauration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `restriction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(90) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `auteur_id` int UNSIGNED NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_restriction_user1_idx` (`auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `rule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `url` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `rumeur` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `text` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `visibility` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rumeur_territoire1_idx` (`territoire_id`),
  KEY `fk_rumeur_user1_idx` (`user_id`),
  KEY `fk_rumeur_gn1_idx` (`gn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `secondary_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `secondary_group_type_id` int NOT NULL,
  `personnage_id` int DEFAULT NULL,
  `topic_id` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_secrete` longtext COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) DEFAULT NULL,
  `materiel` longtext COLLATE utf8mb3_unicode_ci,
  `discord` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `private` tinyint(1) DEFAULT NULL,
  `scenariste_id` int UNSIGNED DEFAULT NULL,
  `show_discord` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_secondary_groupe_secondary_group_type1_idx` (`secondary_group_type_id`),
  KEY `fk_secondary_group_personnage1_idx` (`personnage_id`),
  KEY `fk_secondary_group_topic1_idx` (`topic_id`),
  KEY `IDX_717A91A31674CEC6` (`scenariste_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `secondary_group_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `sort` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domaine_id` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_sort_domaine1_idx` (`domaine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `sorts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domaine_id` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sorts_domaine1_idx` (`domaine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `sphere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `statut` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `technologie` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `documentUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `competence_family_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_technologie_competence_family` (`competence_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `technologies_ressources` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `technologie_id` int UNSIGNED NOT NULL,
  `ressource_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_technologies_ressources_idx` (`technologie_id`),
  KEY `FK_B15E3D68FC6CD52A` (`ressource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `territoire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `territoire_guerre_id` int DEFAULT NULL,
  `appelation_id` int NOT NULL,
  `langue_id` int DEFAULT NULL,
  `religion_id` int DEFAULT NULL,
  `nom` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `capitale` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `politique` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dirigeant` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `population` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `symbole` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tech_level` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type_racial` longtext COLLATE utf8mb3_unicode_ci,
  `inspiration` longtext COLLATE utf8mb3_unicode_ci,
  `armes_predilection` longtext COLLATE utf8mb3_unicode_ci,
  `vetements` longtext COLLATE utf8mb3_unicode_ci,
  `noms_masculin` longtext COLLATE utf8mb3_unicode_ci,
  `noms_feminin` longtext COLLATE utf8mb3_unicode_ci,
  `frontieres` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `geojson` longtext COLLATE utf8mb3_unicode_ci,
  `color` varchar(7) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `groupe_id` int DEFAULT NULL,
  `tresor` int DEFAULT NULL,
  `resistance` int DEFAULT NULL,
  `blason` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_secrete` longtext COLLATE utf8mb3_unicode_ci,
  `statut` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `culture_id` int UNSIGNED DEFAULT NULL,
  `ordre_social` int NOT NULL,
  `topic_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_zone_politique_zone_politique1_idx` (`territoire_id`),
  KEY `fk_territoire_territoire_guerre1_idx` (`territoire_guerre_id`),
  KEY `fk_territoire_appelation1_idx` (`appelation_id`),
  KEY `fk_territoire_langue1_idx` (`langue_id`),
  KEY `fk_territoire_religion1_idx` (`religion_id`),
  KEY `fk_territoire_groupe1_idx` (`groupe_id`),
  KEY `fk_territoire_culture1_idx` (`culture_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_exportation` (
  `territoire_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ressource_id`),
  KEY `IDX_BC24449DD0F97A8` (`territoire_id`),
  KEY `IDX_BC24449DFC6CD52A` (`ressource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_guerre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `puissance` int DEFAULT NULL,
  `puissance_max` int DEFAULT NULL,
  `protection` int DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_has_construction` (
  `territoire_id` int NOT NULL,
  `construction_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`construction_id`),
  KEY `IDX_FEB4D8E9D0F97A8` (`territoire_id`),
  KEY `IDX_FEB4D8E9CF48117A` (`construction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_has_loi` (
  `loi_id` int UNSIGNED NOT NULL,
  `territoire_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`loi_id`),
  KEY `IDX_5470401BAB82AB5` (`loi_id`),
  KEY `IDX_5470401BD0F97A8` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_importation` (
  `territoire_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ressource_id`),
  KEY `IDX_77B99CF6D0F97A8` (`territoire_id`),
  KEY `IDX_77B99CF6FC6CD52A` (`ressource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_ingredient` (
  `territoire_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ingredient_id`),
  KEY `IDX_9B7BF292D0F97A8` (`territoire_id`),
  KEY `IDX_9B7BF292933FE08C` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_langue` (
  `territoire_id` int NOT NULL,
  `langue_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`langue_id`),
  KEY `IDX_C9327BC3D0F97A8` (`territoire_id`),
  KEY `IDX_C9327BC32AADBACD` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `territoire_quete` (
  `territoire_id` int NOT NULL,
  `territoire_cible_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`territoire_cible_id`),
  KEY `IDX_63718DCD0F97A8` (`territoire_id`),
  KEY `IDX_63718DCB011823` (`territoire_cible_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `territoire_religion` (
  `territoire_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`religion_id`),
  KEY `IDX_B23AB2D3D0F97A8` (`territoire_id`),
  KEY `IDX_B23AB2D3B7850CBD` (`religion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `titre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `renomme` int NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `titre_territoire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre_id` int NOT NULL,
  `territoire_id` int NOT NULL,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_titre_territoire_titre1_idx` (`titre_id`),
  KEY `fk_titre_territoire_territoire1_idx` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `tag` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `topic` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(450) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `right` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int DEFAULT NULL,
  `key` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_topic_topic1_idx` (`topic_id`),
  KEY `fk_topic_user1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `trigger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `tag` varchar(45) COLLATE utf8mb3_unicode_ci NOT NULL,
  `done` tinyint(1) NOT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trigger_idx` (`personnage_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `etat_civil_id` int DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salt` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `rights` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `isEnabled` tinyint(1) NOT NULL,
  `confirmationToken` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `timePasswordResetRequested` int UNSIGNED DEFAULT NULL,
  `discr` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `trombineUrl` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastConnectionDate` datetime DEFAULT NULL,
  `personnage_id` int DEFAULT NULL,
  `coeur` int DEFAULT NULL,
  `personnage_secondaire_id` int DEFAULT NULL,
  `roles` json NOT NULL COMMENT '(DC2Type:json)',
  `is_enabled` tinyint DEFAULT NULL,
  `pwd` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email_contact` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_user_etat_civil1_idx` (`etat_civil_id`),
  KEY `fk_user_personnage_secondaire1_idx` (`personnage_secondaire_id`),
  KEY `fk_user_personnage1_idx` (`personnage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_has_restriction` (
  `user_id` int UNSIGNED NOT NULL,
  `restriction_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`restriction_id`),
  KEY `IDX_1A57746A76ED395` (`user_id`),
  KEY `IDX_1A57746E6160631` (`restriction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `watching_user` (
  `post_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`,`user_id`),
  KEY `IDX_FFDC43024B89032C` (`post_id`),
  KEY `IDX_FFDC4302A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

CREATE TABLE IF NOT EXISTS `wt_heroisme_ad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `competence_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `wt_litterature_top` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `Compétence` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;


ALTER TABLE `annonce`
  ADD CONSTRAINT `FK_F65593E5AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `appelation`
  ADD CONSTRAINT `FK_68825BB0F9E65DDB` FOREIGN KEY (`appelation_id`) REFERENCES `appelation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `background`
  ADD CONSTRAINT `FK_BC68B4507A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_BC68B450A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_BC68B450AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `billet`
  ADD CONSTRAINT `FK_1F034AF673A201E5` FOREIGN KEY (`createur_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_1F034AF6AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `bonus`
  ADD CONSTRAINT `FK_9F987F7A15761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`);

ALTER TABLE `chronologie`
  ADD CONSTRAINT `FK_6ECC33A72FE85823` FOREIGN KEY (`zone_politique_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `classe_competence_family_creation`
  ADD CONSTRAINT `FK_4FC70A4B8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_4FC70A4BF7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `classe_competence_family_favorite`
  ADD CONSTRAINT `FK_70EC01E68F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_70EC01E6F7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `classe_competence_family_normale`
  ADD CONSTRAINT `FK_D65491848F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D6549184F7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `competence`
  ADD CONSTRAINT `FK_94D4687F5FB14BA7` FOREIGN KEY (`level_id`) REFERENCES `level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_94D4687FF7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `competence_attribute`
  ADD CONSTRAINT `FK_CECF998615761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_CECF99864ED0D557` FOREIGN KEY (`attribute_type_id`) REFERENCES `attribute_type` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `debriefing`
  ADD CONSTRAINT `FK_CB81225E7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_CB81225EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_CB81225EAFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `document`
  ADD CONSTRAINT `FK_D8698A76A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `document_has_langue`
  ADD CONSTRAINT `FK_1EB6C9432AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_1EB6C943C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `espece_personnage`
  ADD CONSTRAINT `FK_D7C6D24D2D191E7A` FOREIGN KEY (`espece_id`) REFERENCES `espece` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_D7C6D24D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE CASCADE;

ALTER TABLE `experience_gain`
  ADD CONSTRAINT `FK_8485E21D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `experience_usage`
  ADD CONSTRAINT `FK_B3B9226615761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B3B922665E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `gn`
  ADD CONSTRAINT `FK_C16FA3C01F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe`
  ADD CONSTRAINT `FK_4B98C211674CEC6` FOREIGN KEY (`scenariste_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_4B98C2153C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_4B98C21D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_allie`
  ADD CONSTRAINT `FK_8E0758767A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8E075876DA3B93A3` FOREIGN KEY (`groupe_allie_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_bonus`
  ADD CONSTRAINT `FK_CA35B12A69545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  ADD CONSTRAINT `FK_CA35B12A7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`);

ALTER TABLE `groupe_classe`
  ADD CONSTRAINT `FK_E4B943AC7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_E4B943AC8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_enemy`
  ADD CONSTRAINT `FK_AE3294F91AE935F` FOREIGN KEY (`groupe_enemy_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_AE3294F97A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_gn`
  ADD CONSTRAINT `FK_413F11C13776DBA` FOREIGN KEY (`camarilla_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_413F11C30EF1B89` FOREIGN KEY (`suzerin_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_413F11C53C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_413F11C7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_413F11C7B52884` FOREIGN KEY (`intendant_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_413F11C7EAA3A12` FOREIGN KEY (`navigateur_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_413F11CAFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_413F11CB42EB948` FOREIGN KEY (`connetable_id`) REFERENCES `personnage` (`id`);

ALTER TABLE `groupe_has_document`
  ADD CONSTRAINT `FK_B55C428645A3F7E0` FOREIGN KEY (`Document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B55C42867A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_has_ingredient`
  ADD CONSTRAINT `FK_EAACBE7A7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_EAACBE7A933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_has_item`
  ADD CONSTRAINT `FK_D3E5F531126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D3E5F5317A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `groupe_has_ressource`
  ADD CONSTRAINT `FK_2E4F82907A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_2E4F8290FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `heroisme_history`
  ADD CONSTRAINT `FK_23D4BD695E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue`
  ADD CONSTRAINT `FK_688D271A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_document`
  ADD CONSTRAINT `FK_928B0219631F6DBE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_928B02196AB21CC3` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_evenement`
  ADD CONSTRAINT `FK_B5719C3C631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B5719C3CFD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_groupe`
  ADD CONSTRAINT `FK_347A1255631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_347A12557A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_groupe_secondaire`
  ADD CONSTRAINT `FK_5C689C98631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5C689C9865F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_lieu`
  ADD CONSTRAINT `FK_928B0219631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_928B02196AB213CC` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_modification`
  ADD CONSTRAINT `FK_5172570C631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5172570CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `intrigue_has_objectif`
  ADD CONSTRAINT `FK_BE1C5A23157D1AD4` FOREIGN KEY (`objectif_id`) REFERENCES `objectif` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_BE1C5A23631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `item`
  ADD CONSTRAINT `FK_1F1B251EBCFC6D57` FOREIGN KEY (`quality_id`) REFERENCES `quality` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_1F1B251EF520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_1F1B251EF6203804` FOREIGN KEY (`statut_id`) REFERENCES `statut` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `lieu_has_document`
  ADD CONSTRAINT `FK_487D3F926AB213CC` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_487D3F92C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `log_action`
  ADD CONSTRAINT `FK_5236DF30A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `membre`
  ADD CONSTRAINT `FK_F6B4FB295E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_F6B4FB2965F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `merveille`
  ADD CONSTRAINT `FK_D70330D069545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  ADD CONSTRAINT `FK_D70330D0D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`);

ALTER TABLE `message`
  ADD CONSTRAINT `FK_B6BD307F55AB140` FOREIGN KEY (`auteur`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B6BD307FFEA9FF92` FOREIGN KEY (`destinataire`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `notification`
  ADD CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `objet`
  ADD CONSTRAINT `FK_46CD4C3853C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_46CD4C3876C50E4A` FOREIGN KEY (`proprietaire_id`) REFERENCES `proprietaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_46CD4C387E9E4C8C` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_46CD4C38A4DEB784` FOREIGN KEY (`rangement_id`) REFERENCES `rangement` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_46CD4C38D5E86FF` FOREIGN KEY (`etat_id`) REFERENCES `etat` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `objet_carac`
  ADD CONSTRAINT `FK_6B20A761F520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `objet_tag`
  ADD CONSTRAINT `FK_E3164735BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_E3164735F520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `origine_bonus`
  ADD CONSTRAINT `FK_BE69354769545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  ADD CONSTRAINT `FK_BE693547D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE CASCADE;

ALTER TABLE `participant`
  ADD CONSTRAINT `FK_D79F6B1144973C78` FOREIGN KEY (`billet_id`) REFERENCES `billet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D79F6B115E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D79F6B11A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D79F6B11AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D79F6B11E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D79F6B11FA640E02` FOREIGN KEY (`groupe_gn_id`) REFERENCES `groupe_gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `participant_has_restauration`
  ADD CONSTRAINT `FK_D2F2C8B47C6CB929` FOREIGN KEY (`restauration_id`) REFERENCES `restauration` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D2F2C8B49D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `participant_potions_depart`
  ADD CONSTRAINT `FK_D485198A5E315343` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D485198A7126B349` FOREIGN KEY (`potion_id`) REFERENCES `potion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage`
  ADD CONSTRAINT `FK_6AEA486D4296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6AEA486D7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6AEA486D8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6AEA486DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6AEA486DCC80CD12` FOREIGN KEY (`age_id`) REFERENCES `age` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6AEA486DD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_chronologie`
  ADD CONSTRAINT `FK_6ECC33456843` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_competences`
  ADD CONSTRAINT `FK_8AED412315761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8AED41235E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_connaissances`
  ADD CONSTRAINT `FK_CONNAISSANCES` FOREIGN KEY (`connaissance_id`) REFERENCES `connaissance` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_PERSONNAGES_CONNAISSANCES` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_domaines`
  ADD CONSTRAINT `FK_C31CED644272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_C31CED645E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_lignee`
  ADD CONSTRAINT `FK_6ECC33456844` FOREIGN KEY (`parent1_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6ECC33456845` FOREIGN KEY (`parent2_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6ECC33456846` FOREIGN KEY (`lignee_id`) REFERENCES `lignees` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_6ECC33456847` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_potions`
  ADD CONSTRAINT `FK_D485198A5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_D485198A7126B348` FOREIGN KEY (`potion_id`) REFERENCES `potion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_prieres`
  ADD CONSTRAINT `FK_4E610DAC5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_4E610DACA8227EF5` FOREIGN KEY (`priere_id`) REFERENCES `priere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_religions`
  ADD CONSTRAINT `FK_8530B75F423EA53D` FOREIGN KEY (`religion_level_id`) REFERENCES `religion_level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8530B75F5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8530B75FB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnages_sorts`
  ADD CONSTRAINT `FK_8ABC9FD747013001` FOREIGN KEY (`sort_id`) REFERENCES `sort` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8ABC9FD75E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_apprentissage`
  ADD CONSTRAINT `FK_EA259B5115761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`),
  ADD CONSTRAINT `FK_EA259B515E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_EA259B51E455FCC0` FOREIGN KEY (`enseignant_id`) REFERENCES `personnage` (`id`);

ALTER TABLE `personnage_background`
  ADD CONSTRAINT `FK_273D6F455E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_273D6F45A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_273D6F45AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_bonus`
  ADD CONSTRAINT `FK_35CB73405E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`),
  ADD CONSTRAINT `FK_35CB734069545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`);

ALTER TABLE `personnage_has_document`
  ADD CONSTRAINT `FK_EFBB065F45A3F7E0` FOREIGN KEY (`Document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_EFBB065F5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_has_item`
  ADD CONSTRAINT `FK_356CD1EF126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_356CD1EF5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_has_question`
  ADD CONSTRAINT `FK_8125C5671E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8125C5675E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_has_technologie`
  ADD CONSTRAINT `FK_65F62F93261A27D2` FOREIGN KEY (`technologie_id`) REFERENCES `technologie` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_65F62F935E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_has_token`
  ADD CONSTRAINT `FK_95A7144541DEE7B9` FOREIGN KEY (`token_id`) REFERENCES `token` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_95A714455E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_ingredient`
  ADD CONSTRAINT `FK_F0FAA3655E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_F0FAA365933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_langues`
  ADD CONSTRAINT `FK_3D820E582AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_3D820E585E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_religion_description`
  ADD CONSTRAINT `FK_874677E25E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_874677E2B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_ressource`
  ADD CONSTRAINT `FK_A286E0845E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_A286E084FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_secondaire`
  ADD CONSTRAINT `FK_EACE838A8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_secondaires_competences`
  ADD CONSTRAINT `FK_A871317815761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_A8713178E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_secondaires_skills`
  ADD CONSTRAINT `FK_C410AE5015761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_C410AE50E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_secondaire_competence`
  ADD CONSTRAINT `FK_DF6A66D15761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_DF6A66DE6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `personnage_trigger`
  ADD CONSTRAINT `FK_3674375C5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `post`
  ADD CONSTRAINT `FK_5A8A6C8D1F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5A8A6C8D4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `postulant`
  ADD CONSTRAINT `FK_F79395125E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_F793951265F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `post_view`
  ADD CONSTRAINT `FK_37A8CC854B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_37A8CC85A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `priere`
  ADD CONSTRAINT `FK_1111202C75FD4EF9` FOREIGN KEY (`sphere_id`) REFERENCES `sphere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `pugilat_history`
  ADD CONSTRAINT `FK_864C39CB5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `qr_code_scan_log`
  ADD CONSTRAINT `FK_4F1CE7B0126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  ADD CONSTRAINT `FK_4F1CE7B09D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  ADD CONSTRAINT `FK_4F1CE7B0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `quality_valeur`
  ADD CONSTRAINT `FK_A480028F98D3FE22` FOREIGN KEY (`monnaie_id`) REFERENCES `monnaie` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_A480028FBCFC6D57` FOREIGN KEY (`quality_id`) REFERENCES `quality` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `question`
  ADD CONSTRAINT `FK_B6F7494EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `rangement`
  ADD CONSTRAINT `FK_90F17AA6C68BE09C` FOREIGN KEY (`localisation_id`) REFERENCES `localisation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `relecture`
  ADD CONSTRAINT `FK_FC5CF714631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_FC5CF714A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `religion`
  ADD CONSTRAINT `FK_1055F4F91F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `religions_spheres`
  ADD CONSTRAINT `FK_65855EBE75FD4EF9` FOREIGN KEY (`sphere_id`) REFERENCES `sphere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_65855EBEB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `religion_description`
  ADD CONSTRAINT `FK_209A3DCE423EA53D` FOREIGN KEY (`religion_level_id`) REFERENCES `religion_level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_209A3DCEB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `renomme_history`
  ADD CONSTRAINT `FK_40D972425E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `reponse`
  ADD CONSTRAINT `FK_5FB6DEC71E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5FB6DEC79D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `ressource`
  ADD CONSTRAINT `FK_939F45449E795D2F` FOREIGN KEY (`rarete_id`) REFERENCES `rarete` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `restriction`
  ADD CONSTRAINT `FK_7A999BCE60BB6FE6` FOREIGN KEY (`auteur_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `rumeur`
  ADD CONSTRAINT `FK_AD09D960A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_AD09D960AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_AD09D960D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `secondary_group`
  ADD CONSTRAINT `FK_717A91A31674CEC6` FOREIGN KEY (`scenariste_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_717A91A31F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_717A91A35E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_717A91A3B27217D1` FOREIGN KEY (`secondary_group_type_id`) REFERENCES `secondary_group_type` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `sort`
  ADD CONSTRAINT `FK_5124F2224272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `sorts`
  ADD CONSTRAINT `FK_CE3FAA1D4272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `technologie`
  ADD CONSTRAINT `FK_technologie_competence_family` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `technologies_ressources`
  ADD CONSTRAINT `FK_B15E3D68261A27D2` FOREIGN KEY (`technologie_id`) REFERENCES `technologie` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B15E3D68FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire`
  ADD CONSTRAINT `FK_B8655F542AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F54682CA693` FOREIGN KEY (`territoire_guerre_id`) REFERENCES `territoire_guerre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F547A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F54B108249D` FOREIGN KEY (`culture_id`) REFERENCES `culture` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F54B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F54D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B8655F54F9E65DDB` FOREIGN KEY (`appelation_id`) REFERENCES `appelation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_exportation`
  ADD CONSTRAINT `FK_BC24449DD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_BC24449DFC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_has_construction`
  ADD CONSTRAINT `FK_FEB4D8E9CF48117A` FOREIGN KEY (`construction_id`) REFERENCES `construction` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_FEB4D8E9D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_has_loi`
  ADD CONSTRAINT `FK_5470401BAB82AB5` FOREIGN KEY (`loi_id`) REFERENCES `loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_5470401BD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_importation`
  ADD CONSTRAINT `FK_77B99CF6D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_77B99CF6FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_ingredient`
  ADD CONSTRAINT `FK_9B7BF292933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_9B7BF292D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_langue`
  ADD CONSTRAINT `FK_C9327BC32AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_C9327BC3D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `territoire_religion`
  ADD CONSTRAINT `FK_B23AB2D3B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B23AB2D3D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `titre_territoire`
  ADD CONSTRAINT `FK_FA93160ED0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_FA93160ED54FAE5E` FOREIGN KEY (`titre_id`) REFERENCES `titre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `topic`
  ADD CONSTRAINT `FK_9D40DE1B1F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_9D40DE1BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `trigger`
  ADD CONSTRAINT `FK_1A6B0F5D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `user`
  ADD CONSTRAINT `FK_8D93D649191476EE` FOREIGN KEY (`etat_civil_id`) REFERENCES `etat_civil` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8D93D6495E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_8D93D649E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `user_has_restriction`
  ADD CONSTRAINT `FK_1A57746A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_1A57746E6160631` FOREIGN KEY (`restriction_id`) REFERENCES `restriction` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `watching_user`
  ADD CONSTRAINT `FK_FFDC43024B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_FFDC4302A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
