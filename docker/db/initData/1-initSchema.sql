-- MySQL dump 10.13  Distrib 8.4.6, for Linux (aarch64)
--
-- Host: localhost    Database: exporter
-- ------------------------------------------------------
-- Server version	8.4.6

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `age`
--

DROP TABLE IF EXISTS `age`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `age` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bonus` int DEFAULT NULL,
  `enableCreation` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `minimumValue` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `annonce`
--

DROP TABLE IF EXISTS `annonce`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `annonce` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `archive` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_annonce_gn1_idx` (`gn_id`),
  CONSTRAINT `FK_F65593E5AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `appelation`
--

DROP TABLE IF EXISTS `appelation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appelation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appelation_id` int DEFAULT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `titre` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_territoire_denomination_territoire_denomination1_idx` (`appelation_id`),
  CONSTRAINT `FK_68825BB0F9E65DDB` FOREIGN KEY (`appelation_id`) REFERENCES `appelation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attribute_type`
--

DROP TABLE IF EXISTS `attribute_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `background`
--

DROP TABLE IF EXISTS `background`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `background` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int DEFAULT NULL,
  `titre` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_background_groupe1_idx` (`groupe_id`),
  KEY `fk_background_user1_idx` (`user_id`),
  KEY `fk_background_gn1_idx` (`gn_id`),
  CONSTRAINT `FK_BC68B4507A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_BC68B450A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_BC68B450AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=444 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `billet`
--

DROP TABLE IF EXISTS `billet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `createur_id` int unsigned NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  `fedegn` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_billet_user1_idx` (`createur_id`),
  KEY `fk_billet_gn1_idx` (`gn_id`),
  CONSTRAINT `FK_1F034AF673A201E5` FOREIGN KEY (`createur_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_1F034AF6AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus`
--

DROP TABLE IF EXISTS `bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valeur` int DEFAULT NULL,
  `periode` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `competence_id` int DEFAULT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT '(DC2Type:json)',
  `discr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_competence_idx` (`competence_id`),
  CONSTRAINT `FK_9F987F7A15761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`),
  CONSTRAINT `bonus_chk_1` CHECK (json_valid(`json_data`))
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chronologie`
--

DROP TABLE IF EXISTS `chronologie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chronologie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zone_politique_id` int NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `month` int DEFAULT NULL,
  `day` int DEFAULT NULL,
  `visibilite` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_chronologie_zone_politique1_idx` (`zone_politique_id`),
  CONSTRAINT `FK_6ECC33A72FE85823` FOREIGN KEY (`zone_politique_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classe`
--

DROP TABLE IF EXISTS `classe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label_masculin` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `label_feminin` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `image_m` varchar(90) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `image_f` varchar(90) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classe_competence_family_creation`
--

DROP TABLE IF EXISTS `classe_competence_family_creation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_competence_family_creation` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_4FC70A4B8F5EA509` (`classe_id`),
  KEY `IDX_4FC70A4BF7EB2017` (`competence_family_id`),
  CONSTRAINT `FK_4FC70A4B8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_4FC70A4BF7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classe_competence_family_favorite`
--

DROP TABLE IF EXISTS `classe_competence_family_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_competence_family_favorite` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_70EC01E68F5EA509` (`classe_id`),
  KEY `IDX_70EC01E6F7EB2017` (`competence_family_id`),
  CONSTRAINT `FK_70EC01E68F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_70EC01E6F7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classe_competence_family_normale`
--

DROP TABLE IF EXISTS `classe_competence_family_normale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_competence_family_normale` (
  `classe_id` int NOT NULL,
  `competence_family_id` int NOT NULL,
  PRIMARY KEY (`classe_id`,`competence_family_id`),
  KEY `IDX_D65491848F5EA509` (`classe_id`),
  KEY `IDX_D6549184F7EB2017` (`competence_family_id`),
  CONSTRAINT `FK_D65491848F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D6549184F7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `competence`
--

DROP TABLE IF EXISTS `competence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `competence_family_id` int NOT NULL,
  `level_id` int DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `materiel` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_competence_niveau_competence1_idx` (`competence_family_id`),
  KEY `fk_competence_niveau_niveau1_idx` (`level_id`),
  CONSTRAINT `FK_94D4687F5FB14BA7` FOREIGN KEY (`level_id`) REFERENCES `level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_94D4687FF7EB2017` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `competence_attribute`
--

DROP TABLE IF EXISTS `competence_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competence_attribute` (
  `competence_id` int NOT NULL,
  `attribute_type_id` int NOT NULL,
  `value` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`competence_id`,`attribute_type_id`),
  KEY `fk_competence_has_attribute_type_attribute_type1_idx` (`attribute_type_id`),
  KEY `fk_competence_has_attribute_type_competence1_idx` (`competence_id`),
  CONSTRAINT `FK_CECF998615761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_CECF99864ED0D557` FOREIGN KEY (`attribute_type_id`) REFERENCES `attribute_type` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `competence_family`
--

DROP TABLE IF EXISTS `competence_family`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competence_family` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `connaissance`
--

DROP TABLE IF EXISTS `connaissance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `connaissance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext,
  `contraintes` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(255) DEFAULT NULL,
  `niveau` int unsigned NOT NULL DEFAULT '1',
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `discr` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `construction`
--

DROP TABLE IF EXISTS `construction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `construction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `defense` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `culture`
--

DROP TABLE IF EXISTS `culture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `culture` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_complete` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `debriefing`
--

DROP TABLE IF EXISTS `debriefing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debriefing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `gn_id` int DEFAULT NULL,
  `titre` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `player_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_debriefing_groupe1_idx` (`groupe_id`),
  KEY `fk_debriefing_user1_idx` (`user_id`),
  KEY `fk_debriefing_gn1_idx` (`gn_id`),
  KEY `fk_debriefing_player1_idx` (`player_id`) USING BTREE,
  CONSTRAINT `FK_CB81225E7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_CB81225EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_CB81225EAFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `code` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `titre` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cryptage` tinyint(1) NOT NULL,
  `statut` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `auteur` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `impression` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_document_user1_idx` (`user_id`),
  CONSTRAINT `FK_D8698A76A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document_has_langue`
--

DROP TABLE IF EXISTS `document_has_langue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_has_langue` (
  `document_id` int NOT NULL,
  `langue_id` int NOT NULL,
  PRIMARY KEY (`document_id`,`langue_id`),
  KEY `IDX_1EB6C943C33F7837` (`document_id`),
  KEY `IDX_1EB6C9432AADBACD` (`langue_id`),
  CONSTRAINT `FK_1EB6C9432AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_1EB6C943C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `domaine`
--

DROP TABLE IF EXISTS `domaine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domaine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `espece`
--

DROP TABLE IF EXISTS `espece`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `espece` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret` tinyint(1) NOT NULL,
  `description_secrete` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `espece_personnage`
--

DROP TABLE IF EXISTS `espece_personnage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `espece_personnage` (
  `espece_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`espece_id`,`personnage_id`),
  KEY `IDX_D7C6D24D2D191E7A` (`espece_id`),
  KEY `IDX_D7C6D24D5E315342` (`personnage_id`),
  CONSTRAINT `FK_D7C6D24D2D191E7A` FOREIGN KEY (`espece_id`) REFERENCES `espece` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D7C6D24D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `etat`
--

DROP TABLE IF EXISTS `etat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `etat_civil`
--

DROP TABLE IF EXISTS `etat_civil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etat_civil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prenom` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prenom_usage` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `telephone` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `photo` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `date_naissance` datetime DEFAULT NULL,
  `probleme_medicaux` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `personne_a_prevenir` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tel_pap` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fedegn` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5343 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `evenement`
--

DROP TABLE IF EXISTS `evenement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evenement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experience_gain`
--

DROP TABLE IF EXISTS `experience_gain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_gain` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `explanation` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `operation_date` datetime NOT NULL,
  `xp_gain` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experience_gain_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_8485E21D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=33721 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experience_usage`
--

DROP TABLE IF EXISTS `experience_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `competence_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `operation_date` datetime NOT NULL,
  `xp_use` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_experience_usage_competence1_idx` (`competence_id`),
  KEY `fk_experience_usage_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_B3B9226615761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B3B922665E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=47740 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formulaire`
--

DROP TABLE IF EXISTS `formulaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `formulaire` (
  `id` int NOT NULL,
  `title` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gn`
--

DROP TABLE IF EXISTS `gn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `xp_creation` int DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `date_installation_joueur` datetime DEFAULT NULL,
  `date_fin_orga` datetime DEFAULT NULL,
  `adresse` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `billetterie` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `conditions_inscription` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `date_jeu` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_gn_topic1_idx` (`topic_id`),
  CONSTRAINT `FK_C16FA3C01F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe`
--

DROP TABLE IF EXISTS `groupe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scenariste_id` int unsigned DEFAULT NULL,
  `responsable_id` int unsigned DEFAULT NULL,
  `topic_id` int DEFAULT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `code` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `jeu_maritime` tinyint(1) DEFAULT NULL,
  `jeu_strategique` tinyint(1) DEFAULT NULL,
  `classe_open` int DEFAULT NULL,
  `pj` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `materiel` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `lock` tinyint(1) NOT NULL,
  `territoire_id` int DEFAULT NULL,
  `richesse` int DEFAULT NULL,
  `discord` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_membres` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_users1_idx` (`scenariste_id`),
  KEY `fk_groupe_user2_idx` (`responsable_id`),
  KEY `fk_groupe_topic1_idx` (`topic_id`),
  KEY `fk_groupe_territoire1_idx` (`territoire_id`),
  CONSTRAINT `FK_4B98C211674CEC6` FOREIGN KEY (`scenariste_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_4B98C2153C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_4B98C21D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=293 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_allie`
--

DROP TABLE IF EXISTS `groupe_allie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_allie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `groupe_allie_id` int NOT NULL,
  `groupe_accepted` tinyint(1) NOT NULL,
  `groupe_allie_accepted` tinyint(1) NOT NULL,
  `message` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `message_allie` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_allie_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_allie_groupe2_idx` (`groupe_allie_id`),
  CONSTRAINT `FK_8E0758767A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8E075876DA3B93A3` FOREIGN KEY (`groupe_allie_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_bonus`
--

DROP TABLE IF EXISTS `groupe_bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `bonus_id` int NOT NULL,
  `creation_date` datetime NOT NULL,
  `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_bonus_idx` (`bonus_id`),
  KEY `fk_groupe_idx` (`groupe_id`),
  CONSTRAINT `FK_CA35B12A69545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  CONSTRAINT `FK_CA35B12A7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_classe`
--

DROP TABLE IF EXISTS `groupe_classe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `classe_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_classe_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_classe_classe1_idx` (`classe_id`),
  CONSTRAINT `FK_E4B943AC7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_E4B943AC8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1523 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_enemy`
--

DROP TABLE IF EXISTS `groupe_enemy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_enemy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `groupe_enemy_id` int NOT NULL,
  `groupe_peace` tinyint(1) NOT NULL,
  `groupe_enemy_peace` tinyint(1) NOT NULL,
  `message` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `message_enemy` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_enemy_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_enemy_groupe2_idx` (`groupe_enemy_id`),
  CONSTRAINT `FK_AE3294F91AE935F` FOREIGN KEY (`groupe_enemy_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_AE3294F97A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_gn`
--

DROP TABLE IF EXISTS `groupe_gn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_gn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `gn_id` int NOT NULL,
  `responsable_id` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `free` tinyint(1) NOT NULL,
  `code` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
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
  `bateaux_localisation` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `diplomate_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_gn_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_gn_gn1_idx` (`gn_id`),
  KEY `fk_groupe_gn_participant1_idx` (`responsable_id`),
  KEY `IDX_413F11C30EF1B89` (`suzerin_id`),
  KEY `IDX_413F11CB42EB948` (`connetable_id`),
  KEY `IDX_413F11C7B52884` (`intendant_id`),
  KEY `IDX_413F11C7EAA3A12` (`navigateur_id`),
  KEY `IDX_413F11C13776DBA` (`camarilla_id`),
  CONSTRAINT `FK_413F11C13776DBA` FOREIGN KEY (`camarilla_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_413F11C30EF1B89` FOREIGN KEY (`suzerin_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_413F11C53C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_413F11C7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_413F11C7B52884` FOREIGN KEY (`intendant_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_413F11C7EAA3A12` FOREIGN KEY (`navigateur_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_413F11CAFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_413F11CB42EB948` FOREIGN KEY (`connetable_id`) REFERENCES `personnage` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=862 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_has_document`
--

DROP TABLE IF EXISTS `groupe_has_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_has_document` (
  `groupe_id` int NOT NULL,
  `Document_id` int NOT NULL,
  PRIMARY KEY (`groupe_id`,`Document_id`),
  KEY `IDX_B55C42867A45358C` (`groupe_id`),
  KEY `IDX_B55C4286C33F7837` (`Document_id`),
  CONSTRAINT `FK_B55C428645A3F7E0` FOREIGN KEY (`Document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B55C42867A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_has_ingredient`
--

DROP TABLE IF EXISTS `groupe_has_ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_has_ingredient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_has_ingredient_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_has_ingredient_ingredient1_idx` (`ingredient_id`),
  CONSTRAINT `FK_EAACBE7A7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_EAACBE7A933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_has_item`
--

DROP TABLE IF EXISTS `groupe_has_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_has_item` (
  `groupe_id` int NOT NULL,
  `item_id` int unsigned NOT NULL,
  PRIMARY KEY (`groupe_id`,`item_id`),
  KEY `IDX_D3E5F5317A45358C` (`groupe_id`),
  KEY `IDX_D3E5F531126F525E` (`item_id`),
  CONSTRAINT `FK_D3E5F531126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D3E5F5317A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_has_ressource`
--

DROP TABLE IF EXISTS `groupe_has_ressource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_has_ressource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groupe_has_ressource_groupe1_idx` (`groupe_id`),
  KEY `fk_groupe_has_ressource_ressource1_idx` (`ressource_id`),
  CONSTRAINT `FK_2E4F82907A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_2E4F8290FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groupe_langue`
--

DROP TABLE IF EXISTS `groupe_langue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_langue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `couleur` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `heroisme_history`
--

DROP TABLE IF EXISTS `heroisme_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `heroisme_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `date` date NOT NULL,
  `heroisme` int NOT NULL,
  `explication` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_heroisme_history_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_23D4BD695E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=476 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ingredient`
--

DROP TABLE IF EXISTS `ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `niveau` int NOT NULL,
  `dose` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue`
--

DROP TABLE IF EXISTS `intrigue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `titre` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `resolution` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `state` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_user1_idx` (`user_id`),
  CONSTRAINT `FK_688D271A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_document`
--

DROP TABLE IF EXISTS `intrigue_has_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_document` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `document_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_document_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_document_document1_idx` (`document_id`),
  CONSTRAINT `FK_928B0219631F6DBE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_928B02196AB21CC3` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_evenement`
--

DROP TABLE IF EXISTS `intrigue_has_evenement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_evenement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `evenement_id` int unsigned NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_evenement_evenement1_idx` (`evenement_id`),
  KEY `fk_intrigue_has_evenement_intrigue1_idx` (`intrigue_id`),
  CONSTRAINT `FK_B5719C3C631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B5719C3CFD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_groupe`
--

DROP TABLE IF EXISTS `intrigue_has_groupe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_groupe` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `groupe_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_groupe_groupe1_idx` (`groupe_id`),
  KEY `fk_intrigue_has_groupe_intrigue1_idx` (`intrigue_id`),
  CONSTRAINT `FK_347A1255631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_347A12557A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=472 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_groupe_secondaire`
--

DROP TABLE IF EXISTS `intrigue_has_groupe_secondaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_groupe_secondaire` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `secondary_group_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_groupe_secondaire_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_groupe_secondaire_secondary_group1_idx` (`secondary_group_id`),
  CONSTRAINT `FK_5C689C98631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5C689C9865F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_lieu`
--

DROP TABLE IF EXISTS `intrigue_has_lieu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_lieu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `lieu_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_lieu_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_lieu_lieu1_idx` (`lieu_id`),
  CONSTRAINT `FK_928B0219631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_928B02196AB213CC` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_modification`
--

DROP TABLE IF EXISTS `intrigue_has_modification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_modification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_modification_intrigue1_idx` (`intrigue_id`),
  KEY `fk_intrigue_has_modification_user1_idx` (`user_id`),
  CONSTRAINT `FK_5172570C631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5172570CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=344 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intrigue_has_objectif`
--

DROP TABLE IF EXISTS `intrigue_has_objectif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `intrigue_has_objectif` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `intrigue_id` int unsigned NOT NULL,
  `objectif_id` int unsigned NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_intrigue_has_objectif_objectif1_idx` (`objectif_id`),
  KEY `fk_intrigue_has_objectif_intrigue1_idx` (`intrigue_id`),
  CONSTRAINT `FK_BE1C5A23157D1AD4` FOREIGN KEY (`objectif_id`) REFERENCES `objectif` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_BE1C5A23631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `identification` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `special` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `couleur` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `objet_id` int NOT NULL,
  `quantite` int NOT NULL,
  `description_secrete` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_scenariste` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_item_statut1_idx` (`statut_id`),
  KEY `fk_item_qualite1_idx` (`quality_id`),
  KEY `fk_item_objet1_idx` (`objet_id`),
  CONSTRAINT `FK_1F1B251EBCFC6D57` FOREIGN KEY (`quality_id`) REFERENCES `quality` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_1F1B251EF520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_1F1B251EF6203804` FOREIGN KEY (`statut_id`) REFERENCES `statut` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=724 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_bak`
--

DROP TABLE IF EXISTS `item_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_bak` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `statut_id` int DEFAULT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `numero` int NOT NULL,
  `identification` int NOT NULL,
  `special` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `couleur` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `objet_id` int NOT NULL,
  `quantite` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_item_statut1_idx` (`statut_id`),
  KEY `fk_item_qualite1_idx` (`quality_id`),
  KEY `fk_item_objet1_idx` (`objet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `langue`
--

DROP TABLE IF EXISTS `langue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `langue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `diffusion` int DEFAULT NULL,
  `groupe_langue_id` int NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupe_langue_id_idx` (`groupe_langue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `level`
--

DROP TABLE IF EXISTS `level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `index` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `cout` int DEFAULT NULL,
  `cout_favori` int DEFAULT NULL,
  `cout_meconu` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lieu`
--

DROP TABLE IF EXISTS `lieu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lieu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lieu_has_document`
--

DROP TABLE IF EXISTS `lieu_has_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lieu_has_document` (
  `document_id` int NOT NULL,
  `lieu_id` int NOT NULL,
  PRIMARY KEY (`lieu_id`,`document_id`),
  KEY `IDX_487D3F92C33F7837` (`document_id`),
  KEY `IDX_487D3F926AB213CC` (`lieu_id`),
  CONSTRAINT `FK_487D3F926AB213CC` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_487D3F92C33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lignees`
--

DROP TABLE IF EXISTS `lignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lignees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `localisation`
--

DROP TABLE IF EXISTS `localisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `localisation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `precision` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_action`
--

DROP TABLE IF EXISTS `log_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_action` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `data` json DEFAULT NULL COMMENT '(DC2Type:json)',
  `date` datetime NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5236DF30A76ED395` (`user_id`),
  CONSTRAINT `FK_5236DF30A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9532 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `loi`
--

DROP TABLE IF EXISTS `loi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `membre`
--

DROP TABLE IF EXISTS `membre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `secondary_group_id` int NOT NULL,
  `secret` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `private` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_groupe_secondaire_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_groupe_secondaire_secondary_group1_idx` (`secondary_group_id`),
  CONSTRAINT `FK_F6B4FB295E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_F6B4FB2965F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1928 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `merveille`
--

DROP TABLE IF EXISTS `merveille`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merveille` (
  `id` int NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `bonus_id` int DEFAULT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_scenariste` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_cartographe` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `statut` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `date_destruction` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D70330D0D0F97A8` (`territoire_id`),
  KEY `IDX_D70330D069545666` (`bonus_id`),
  CONSTRAINT `FK_D70330D069545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  CONSTRAINT `FK_D70330D0D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `auteur` int unsigned NOT NULL,
  `destinataire` int unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `lu` tinyint(1) DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_message_user1_idx` (`auteur`),
  KEY `fk_message_user2_idx` (`destinataire`),
  CONSTRAINT `FK_B6BD307F55AB140` FOREIGN KEY (`auteur`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B6BD307FFEA9FF92` FOREIGN KEY (`destinataire`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9071 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `monnaie`
--

DROP TABLE IF EXISTS `monnaie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monnaie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime DEFAULT NULL,
  `url` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notification_user1_idx` (`user_id`),
  CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=18959 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `objectif`
--

DROP TABLE IF EXISTS `objectif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objectif` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `objet`
--

DROP TABLE IF EXISTS `objet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `etat_id` int DEFAULT NULL,
  `proprietaire_id` int DEFAULT NULL,
  `responsable_id` int unsigned DEFAULT NULL,
  `photo_id` int DEFAULT NULL,
  `rangement_id` int NOT NULL,
  `numero` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `nombre` int DEFAULT NULL,
  `cout` double DEFAULT NULL,
  `budget` double DEFAULT NULL,
  `investissement` tinyint(1) DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_objet_etat1_idx` (`etat_id`),
  KEY `fk_objet_possesseur1_idx` (`proprietaire_id`),
  KEY `fk_objet_users1_idx` (`responsable_id`),
  KEY `fk_objet_photo1_idx` (`photo_id`),
  KEY `fk_objet_rangement1_idx` (`rangement_id`),
  CONSTRAINT `FK_46CD4C3853C59D72` FOREIGN KEY (`responsable_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_46CD4C3876C50E4A` FOREIGN KEY (`proprietaire_id`) REFERENCES `proprietaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_46CD4C387E9E4C8C` FOREIGN KEY (`photo_id`) REFERENCES `photo` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_46CD4C38A4DEB784` FOREIGN KEY (`rangement_id`) REFERENCES `rangement` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_46CD4C38D5E86FF` FOREIGN KEY (`etat_id`) REFERENCES `etat` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1294 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `objet_carac`
--

DROP TABLE IF EXISTS `objet_carac`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objet_carac` (
  `id` int NOT NULL AUTO_INCREMENT,
  `objet_id` int NOT NULL,
  `taille` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `poid` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `couleur` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_objet_carac_objet1_idx` (`objet_id`),
  CONSTRAINT `FK_6B20A761F520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `objet_tag`
--

DROP TABLE IF EXISTS `objet_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objet_tag` (
  `objet_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`objet_id`,`tag_id`),
  KEY `IDX_E3164735F520CF5A` (`objet_id`),
  KEY `IDX_E3164735BAD26311` (`tag_id`),
  CONSTRAINT `FK_E3164735BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_E3164735F520CF5A` FOREIGN KEY (`objet_id`) REFERENCES `objet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `origine_bonus`
--

DROP TABLE IF EXISTS `origine_bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `origine_bonus` (
  `bonus_id` int NOT NULL,
  `territoire_id` int NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bonus_territoire` (`bonus_id`,`territoire_id`),
  KEY `IDX_BE69354769545666` (`bonus_id`),
  KEY `IDX_BE693547D0F97A8` (`territoire_id`),
  CONSTRAINT `FK_BE69354769545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`),
  CONSTRAINT `FK_BE693547D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `participant`
--

DROP TABLE IF EXISTS `participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `subscription_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `personnage_secondaire_id` int DEFAULT NULL,
  `personnage_id` int DEFAULT NULL,
  `billet_id` int DEFAULT NULL,
  `billet_date` datetime DEFAULT NULL,
  `groupe_gn_id` int DEFAULT NULL,
  `valide_ci_le` datetime DEFAULT NULL,
  `couchage` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `special` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_joueur_gn_gn1_idx` (`gn_id`),
  KEY `fk_joueur_gn_user1_idx` (`user_id`),
  KEY `fk_participant_personnage_secondaire1_idx` (`personnage_secondaire_id`),
  KEY `fk_participant_personnage1_idx` (`personnage_id`),
  KEY `fk_participant_billet1_idx` (`billet_id`),
  KEY `fk_participant_groupe_gn1_idx` (`groupe_gn_id`),
  CONSTRAINT `FK_D79F6B1144973C78` FOREIGN KEY (`billet_id`) REFERENCES `billet` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D79F6B115E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D79F6B11A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D79F6B11AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D79F6B11E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D79F6B11FA640E02` FOREIGN KEY (`groupe_gn_id`) REFERENCES `groupe_gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10167 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `participant_has_restauration`
--

DROP TABLE IF EXISTS `participant_has_restauration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participant_has_restauration` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` int NOT NULL,
  `restauration_id` int NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_participant_has_restauration_participant1_idx` (`participant_id`),
  KEY `fk_participant_has_restauration_restauration1_idx` (`restauration_id`),
  CONSTRAINT `FK_D2F2C8B47C6CB929` FOREIGN KEY (`restauration_id`) REFERENCES `restauration` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D2F2C8B49D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3187 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `participant_potions_depart`
--

DROP TABLE IF EXISTS `participant_potions_depart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participant_potions_depart` (
  `participant_id` int NOT NULL,
  `potion_id` int NOT NULL,
  PRIMARY KEY (`participant_id`,`potion_id`),
  KEY `IDX_D485198A5E315343` (`participant_id`),
  KEY `IDX_D485198A7126B349` (`potion_id`),
  CONSTRAINT `FK_D485198A5E315343` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D485198A7126B349` FOREIGN KEY (`potion_id`) REFERENCES `potion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage`
--

DROP TABLE IF EXISTS `personnage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int DEFAULT NULL,
  `classe_id` int NOT NULL,
  `age_id` int NOT NULL,
  `genre_id` int NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `surnom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `intrigue` tinyint(1) DEFAULT NULL,
  `renomme` int DEFAULT NULL,
  `photo` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `xp` int DEFAULT NULL,
  `territoire_id` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `materiel` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `vivant` tinyint(1) NOT NULL,
  `age_reel` int DEFAULT NULL,
  `trombineUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
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
  KEY `fk_personnage_user1_idx` (`user_id`),
  CONSTRAINT `FK_6AEA486D4296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6AEA486D7A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6AEA486D8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6AEA486DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6AEA486DCC80CD12` FOREIGN KEY (`age_id`) REFERENCES `age` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6AEA486DD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6021 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_apprentissage`
--

DROP TABLE IF EXISTS `personnage_apprentissage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_apprentissage` (
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
  KEY `IDX_EA259B5115761DAB` (`competence_id`),
  CONSTRAINT `FK_EA259B5115761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`),
  CONSTRAINT `FK_EA259B515E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_EA259B51E455FCC0` FOREIGN KEY (`enseignant_id`) REFERENCES `personnage` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_background`
--

DROP TABLE IF EXISTS `personnage_background`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_background` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `visibility` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_background_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_background_user1_idx` (`user_id`),
  KEY `fk_personnage_background_gn1_idx` (`gn_id`),
  CONSTRAINT `FK_273D6F455E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_273D6F45A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_273D6F45AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1792 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_bonus`
--

DROP TABLE IF EXISTS `personnage_bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int DEFAULT NULL,
  `bonus_id` int NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `status` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_35CB734069545666` (`bonus_id`,`personnage_id`) USING BTREE,
  KEY `IDX_35CB73405E315342` (`personnage_id`),
  CONSTRAINT `FK_35CB73405E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`),
  CONSTRAINT `FK_35CB734069545666` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_has_document`
--

DROP TABLE IF EXISTS `personnage_has_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_has_document` (
  `personnage_id` int NOT NULL,
  `Document_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`Document_id`),
  KEY `IDX_EFBB065F5E315342` (`personnage_id`),
  KEY `IDX_EFBB065F45A3F7E0` (`Document_id`),
  CONSTRAINT `FK_EFBB065F45A3F7E0` FOREIGN KEY (`Document_id`) REFERENCES `document` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_EFBB065F5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_has_item`
--

DROP TABLE IF EXISTS `personnage_has_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_has_item` (
  `personnage_id` int NOT NULL,
  `item_id` int unsigned NOT NULL,
  PRIMARY KEY (`personnage_id`,`item_id`),
  KEY `IDX_356CD1EF5E315342` (`personnage_id`),
  KEY `IDX_356CD1EF126F525E` (`item_id`),
  CONSTRAINT `FK_356CD1EF126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_356CD1EF5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_has_question`
--

DROP TABLE IF EXISTS `personnage_has_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_has_question` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `question_id` int unsigned NOT NULL,
  `reponse` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_has_question_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_has_question_question1_idx` (`question_id`),
  CONSTRAINT `FK_8125C5671E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8125C5675E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_has_technologie`
--

DROP TABLE IF EXISTS `personnage_has_technologie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_has_technologie` (
  `personnage_id` int NOT NULL,
  `technologie_id` int unsigned NOT NULL,
  PRIMARY KEY (`personnage_id`,`technologie_id`),
  KEY `IDX_65F62F935E315342` (`personnage_id`),
  KEY `IDX_65F62F93261A27D2` (`technologie_id`),
  CONSTRAINT `FK_65F62F93261A27D2` FOREIGN KEY (`technologie_id`) REFERENCES `technologie` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_65F62F935E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_has_token`
--

DROP TABLE IF EXISTS `personnage_has_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_has_token` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `token_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_95A714455E315342` (`personnage_id`),
  KEY `fk_personnage_has_token_token1_idx` (`token_id`),
  CONSTRAINT `FK_95A7144541DEE7B9` FOREIGN KEY (`token_id`) REFERENCES `token` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_95A714455E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=27119 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_ingredient`
--

DROP TABLE IF EXISTS `personnage_ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_ingredient` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `nombre` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F0FAA3655E315342` (`personnage_id`),
  KEY `fk_personnage_ingredient_ingredient1_idx` (`ingredient_id`),
  CONSTRAINT `FK_F0FAA3655E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_F0FAA365933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=310 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_langues`
--

DROP TABLE IF EXISTS `personnage_langues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_langues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `langue_id` int NOT NULL,
  `source` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_langues_personnage1_idx` (`personnage_id`),
  KEY `fk_personnage_langues_langue1_idx` (`langue_id`),
  CONSTRAINT `FK_3D820E582AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_3D820E585E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=30437 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_religion_description`
--

DROP TABLE IF EXISTS `personnage_religion_description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_religion_description` (
  `personnage_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`religion_id`),
  KEY `IDX_874677E25E315342` (`personnage_id`),
  KEY `IDX_874677E2B7850CBD` (`religion_id`),
  CONSTRAINT `FK_874677E25E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_874677E2B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_ressource`
--

DROP TABLE IF EXISTS `personnage_ressource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_ressource` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  `nombre` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A286E0845E315342` (`personnage_id`),
  KEY `fk_personnage_ressource_ressource1_idx` (`ressource_id`),
  CONSTRAINT `FK_A286E0845E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_A286E084FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_secondaire`
--

DROP TABLE IF EXISTS `personnage_secondaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_secondaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `classe_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_classe1_idx` (`classe_id`),
  CONSTRAINT `FK_EACE838A8F5EA509` FOREIGN KEY (`classe_id`) REFERENCES `classe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_secondaire_competence`
--

DROP TABLE IF EXISTS `personnage_secondaire_competence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_secondaire_competence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_competences_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaire_competences_competence1_idx` (`competence_id`),
  CONSTRAINT `FK_DF6A66D15761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_DF6A66DE6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_secondaires_competences`
--

DROP TABLE IF EXISTS `personnage_secondaires_competences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_secondaires_competences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaires_competences_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaires_competences_competence1_idx` (`competence_id`),
  CONSTRAINT `FK_A871317815761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_A8713178E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_secondaires_skills`
--

DROP TABLE IF EXISTS `personnage_secondaires_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_secondaires_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_secondaire_id` int NOT NULL,
  `competence_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_secondaire_skills_personnage_secondaire_idx` (`personnage_secondaire_id`),
  KEY `fk_personnage_secondaire_skills_competence1_idx` (`competence_id`),
  CONSTRAINT `FK_C410AE5015761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_C410AE50E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnage_trigger`
--

DROP TABLE IF EXISTS `personnage_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnage_trigger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `tag` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `done` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trigger_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_3674375C5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=47477 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_chronologie`
--

DROP TABLE IF EXISTS `personnages_chronologie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_chronologie` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `evenement` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `annee` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_6ECC33456843` (`personnage_id`),
  CONSTRAINT `FK_6ECC33456843` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=18933 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_competences`
--

DROP TABLE IF EXISTS `personnages_competences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_competences` (
  `competence_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`competence_id`,`personnage_id`),
  KEY `IDX_8AED412315761DAB` (`competence_id`),
  KEY `IDX_8AED41235E315342` (`personnage_id`),
  CONSTRAINT `FK_8AED412315761DAB` FOREIGN KEY (`competence_id`) REFERENCES `competence` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8AED41235E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_connaissances`
--

DROP TABLE IF EXISTS `personnages_connaissances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_connaissances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `connaissance_id` int unsigned DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_PERSONNAGES_CONNAISSANCES` (`personnage_id`),
  KEY `FK_CONNAISSANCES` (`connaissance_id`),
  CONSTRAINT `FK_CONNAISSANCES` FOREIGN KEY (`connaissance_id`) REFERENCES `connaissance` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_PERSONNAGES_CONNAISSANCES` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_domaines`
--

DROP TABLE IF EXISTS `personnages_domaines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_domaines` (
  `personnage_id` int NOT NULL,
  `domaine_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`domaine_id`),
  KEY `IDX_C31CED645E315342` (`personnage_id`),
  KEY `IDX_C31CED644272FC9F` (`domaine_id`),
  CONSTRAINT `FK_C31CED644272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_C31CED645E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_lignee`
--

DROP TABLE IF EXISTS `personnages_lignee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_lignee` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `parent1_id` int DEFAULT NULL,
  `parent2_id` int DEFAULT NULL,
  `lignee_id` int unsigned DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'extended',
  PRIMARY KEY (`id`),
  KEY `FK_6ECC33456847` (`personnage_id`),
  KEY `FK_6ECC33456844` (`parent1_id`),
  KEY `FK_6ECC33456845` (`parent2_id`),
  KEY `FK_6ECC33456846` (`lignee_id`),
  CONSTRAINT `FK_6ECC33456844` FOREIGN KEY (`parent1_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6ECC33456845` FOREIGN KEY (`parent2_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6ECC33456846` FOREIGN KEY (`lignee_id`) REFERENCES `lignees` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_6ECC33456847` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_potions`
--

DROP TABLE IF EXISTS `personnages_potions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_potions` (
  `personnage_id` int NOT NULL,
  `potion_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`potion_id`),
  KEY `IDX_D485198A5E315342` (`personnage_id`),
  KEY `IDX_D485198A7126B348` (`potion_id`),
  CONSTRAINT `FK_D485198A5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_D485198A7126B348` FOREIGN KEY (`potion_id`) REFERENCES `potion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_prieres`
--

DROP TABLE IF EXISTS `personnages_prieres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_prieres` (
  `priere_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  PRIMARY KEY (`priere_id`,`personnage_id`),
  KEY `IDX_4E610DACA8227EF5` (`priere_id`),
  KEY `IDX_4E610DAC5E315342` (`personnage_id`),
  CONSTRAINT `FK_4E610DAC5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_4E610DACA8227EF5` FOREIGN KEY (`priere_id`) REFERENCES `priere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_religions`
--

DROP TABLE IF EXISTS `personnages_religions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_religions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `religion_id` int NOT NULL,
  `religion_level_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_personnage_religion_religion1_idx` (`religion_id`),
  KEY `fk_personnage_religion_religion_level1_idx` (`religion_level_id`),
  KEY `fk_personnages_religions_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_8530B75F423EA53D` FOREIGN KEY (`religion_level_id`) REFERENCES `religion_level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8530B75F5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8530B75FB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7237 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personnages_sorts`
--

DROP TABLE IF EXISTS `personnages_sorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personnages_sorts` (
  `personnage_id` int NOT NULL,
  `sort_id` int NOT NULL,
  PRIMARY KEY (`personnage_id`,`sort_id`),
  KEY `IDX_8ABC9FD75E315342` (`personnage_id`),
  KEY `IDX_8ABC9FD747013001` (`sort_id`),
  CONSTRAINT `FK_8ABC9FD747013001` FOREIGN KEY (`sort_id`) REFERENCES `sort` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8ABC9FD75E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `photo`
--

DROP TABLE IF EXISTS `photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `photo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `extension` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `real_name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` longblob,
  `creation_date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `filename` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=954 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `post_id` int DEFAULT NULL,
  `title` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_post_topic1_idx` (`topic_id`),
  KEY `fk_post_user1_idx` (`user_id`),
  KEY `fk_post_post1_idx` (`post_id`),
  CONSTRAINT `FK_5A8A6C8D1F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5A8A6C8D4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6074 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post_view`
--

DROP TABLE IF EXISTS `post_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_view` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_post_view_post1_idx` (`post_id`),
  KEY `fk_post_view_user1_idx` (`user_id`),
  CONSTRAINT `FK_37A8CC854B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_37A8CC85A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=338538 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `postulant`
--

DROP TABLE IF EXISTS `postulant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `postulant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `secondary_group_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `date` datetime DEFAULT NULL,
  `explanation` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `waiting` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_postulant_secondary_group1_idx` (`secondary_group_id`),
  KEY `fk_postulant_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_F79395125E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_F793951265F50501` FOREIGN KEY (`secondary_group_id`) REFERENCES `secondary_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1818 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `potion`
--

DROP TABLE IF EXISTS `potion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `potion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `niveau` int NOT NULL,
  `numero` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `priere`
--

DROP TABLE IF EXISTS `priere`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `priere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `annonce` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `sphere_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_priere_sphere1_idx` (`sphere_id`),
  CONSTRAINT `FK_1111202C75FD4EF9` FOREIGN KEY (`sphere_id`) REFERENCES `sphere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proprietaire`
--

DROP TABLE IF EXISTS `proprietaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proprietaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `adresse` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mail` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tel` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pugilat_history`
--

DROP TABLE IF EXISTS `pugilat_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pugilat_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `date` date NOT NULL,
  `pugilat` int NOT NULL,
  `explication` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pugilat_history_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_864C39CB5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qr_code_scan_log`
--

DROP TABLE IF EXISTS `qr_code_scan_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_code_scan_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `participant_id` int DEFAULT NULL,
  `item_id` int unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  `allowed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4F1CE7B0A76ED395` (`user_id`),
  KEY `IDX_4F1CE7B09D1C3019` (`participant_id`),
  KEY `IDX_4F1CE7B0126F525E` (`item_id`),
  CONSTRAINT `FK_4F1CE7B0126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  CONSTRAINT `FK_4F1CE7B09D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`),
  CONSTRAINT `FK_4F1CE7B0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quality`
--

DROP TABLE IF EXISTS `quality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `numero` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quality_valeur`
--

DROP TABLE IF EXISTS `quality_valeur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_valeur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quality_id` int NOT NULL,
  `monnaie_id` int NOT NULL,
  `nombre` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_quality_valeur_qualite1_idx` (`quality_id`),
  KEY `fk_quality_valeur_monnaie1_idx` (`monnaie_id`),
  CONSTRAINT `FK_A480028F98D3FE22` FOREIGN KEY (`monnaie_id`) REFERENCES `monnaie` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_A480028FBCFC6D57` FOREIGN KEY (`quality_id`) REFERENCES `quality` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `choix` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_question_user1_idx` (`user_id`),
  CONSTRAINT `FK_B6F7494EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rangement`
--

DROP TABLE IF EXISTS `rangement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rangement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `localisation_id` int DEFAULT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `precision` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rangement_localisation1_idx` (`localisation_id`),
  CONSTRAINT `FK_90F17AA6C68BE09C` FOREIGN KEY (`localisation_id`) REFERENCES `localisation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rarete`
--

DROP TABLE IF EXISTS `rarete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rarete` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relecture`
--

DROP TABLE IF EXISTS `relecture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relecture` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `intrigue_id` int unsigned NOT NULL,
  `date` datetime NOT NULL,
  `statut` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `remarque` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_relecture_user1_idx` (`user_id`),
  KEY `fk_relecture_intrigue1_idx` (`intrigue_id`),
  CONSTRAINT `FK_FC5CF714631F6BDE` FOREIGN KEY (`intrigue_id`) REFERENCES `intrigue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_FC5CF714A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `religion`
--

DROP TABLE IF EXISTS `religion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `religion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `blason` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_orga` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_fervent` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_pratiquant` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `description_fanatique` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_religion_topic1_idx` (`topic_id`),
  CONSTRAINT `FK_1055F4F91F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `religion_description`
--

DROP TABLE IF EXISTS `religion_description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `religion_description` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `religion_id` int NOT NULL,
  `religion_level_id` int NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_religion_description_religion1_idx` (`religion_id`),
  KEY `fk_religion_description_religion_level1_idx` (`religion_level_id`),
  CONSTRAINT `FK_209A3DCE423EA53D` FOREIGN KEY (`religion_level_id`) REFERENCES `religion_level` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_209A3DCEB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `religion_level`
--

DROP TABLE IF EXISTS `religion_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `religion_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `index` int NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `religions_spheres`
--

DROP TABLE IF EXISTS `religions_spheres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `religions_spheres` (
  `sphere_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`sphere_id`,`religion_id`),
  KEY `IDX_65855EBE75FD4EF9` (`sphere_id`),
  KEY `IDX_65855EBEB7850CBD` (`religion_id`),
  CONSTRAINT `FK_65855EBE75FD4EF9` FOREIGN KEY (`sphere_id`) REFERENCES `sphere` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_65855EBEB7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `renomme_history`
--

DROP TABLE IF EXISTS `renomme_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `renomme_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `renomme` int NOT NULL,
  `explication` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_renomme_history_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_40D972425E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7681 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reponse`
--

DROP TABLE IF EXISTS `reponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reponse` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` int NOT NULL,
  `question_id` int unsigned NOT NULL,
  `reponse` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reponse_participant1_idx` (`participant_id`),
  KEY `fk_reponse_idx` (`question_id`),
  CONSTRAINT `FK_5FB6DEC71E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5FB6DEC79D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ressource`
--

DROP TABLE IF EXISTS `ressource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ressource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rarete_id` int NOT NULL,
  `label` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ressource_rarete1_idx` (`rarete_id`),
  CONSTRAINT `FK_939F45449E795D2F` FOREIGN KEY (`rarete_id`) REFERENCES `rarete` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `restauration`
--

DROP TABLE IF EXISTS `restauration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restauration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `restriction`
--

DROP TABLE IF EXISTS `restriction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `restriction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(90) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `auteur_id` int unsigned NOT NULL,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_restriction_user1_idx` (`auteur_id`),
  CONSTRAINT `FK_7A999BCE60BB6FE6` FOREIGN KEY (`auteur_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rule`
--

DROP TABLE IF EXISTS `rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `url` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rumeur`
--

DROP TABLE IF EXISTS `rumeur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rumeur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `visibility` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gn_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rumeur_territoire1_idx` (`territoire_id`),
  KEY `fk_rumeur_user1_idx` (`user_id`),
  KEY `fk_rumeur_gn1_idx` (`gn_id`),
  CONSTRAINT `FK_AD09D960A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_AD09D960AFC9C052` FOREIGN KEY (`gn_id`) REFERENCES `gn` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_AD09D960D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `secondary_group`
--

DROP TABLE IF EXISTS `secondary_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `secondary_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `secondary_group_type_id` int NOT NULL,
  `personnage_id` int DEFAULT NULL,
  `topic_id` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description_secrete` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `secret` tinyint(1) DEFAULT NULL,
  `materiel` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discord` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `private` tinyint(1) DEFAULT NULL,
  `scenariste_id` int unsigned DEFAULT NULL,
  `show_discord` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_secondary_groupe_secondary_group_type1_idx` (`secondary_group_type_id`),
  KEY `fk_secondary_group_personnage1_idx` (`personnage_id`),
  KEY `fk_secondary_group_topic1_idx` (`topic_id`),
  KEY `IDX_717A91A31674CEC6` (`scenariste_id`),
  CONSTRAINT `FK_717A91A31674CEC6` FOREIGN KEY (`scenariste_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_717A91A31F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_717A91A35E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_717A91A3B27217D1` FOREIGN KEY (`secondary_group_type_id`) REFERENCES `secondary_group_type` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `secondary_group_type`
--

DROP TABLE IF EXISTS `secondary_group_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `secondary_group_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sort`
--

DROP TABLE IF EXISTS `sort`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sort` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domaine_id` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_sort_domaine1_idx` (`domaine_id`),
  CONSTRAINT `FK_5124F2224272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sorts`
--

DROP TABLE IF EXISTS `sorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sorts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domaine_id` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `niveau` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sorts_domaine1_idx` (`domaine_id`),
  CONSTRAINT `FK_CE3FAA1D4272FC9F` FOREIGN KEY (`domaine_id`) REFERENCES `domaine` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sphere`
--

DROP TABLE IF EXISTS `sphere`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sphere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statut`
--

DROP TABLE IF EXISTS `statut`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statut` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `technologie`
--

DROP TABLE IF EXISTS `technologie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `technologie` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` tinyint(1) NOT NULL DEFAULT '0',
  `documentUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `competence_family_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_technologie_competence_family` (`competence_family_id`),
  CONSTRAINT `FK_technologie_competence_family` FOREIGN KEY (`competence_family_id`) REFERENCES `competence_family` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `technologies_ressources`
--

DROP TABLE IF EXISTS `technologies_ressources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `technologies_ressources` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `technologie_id` int unsigned NOT NULL,
  `ressource_id` int NOT NULL,
  `quantite` int NOT NULL,
  `discr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_technologies_ressources_idx` (`technologie_id`),
  KEY `FK_B15E3D68FC6CD52A` (`ressource_id`),
  CONSTRAINT `FK_B15E3D68261A27D2` FOREIGN KEY (`technologie_id`) REFERENCES `technologie` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_B15E3D68FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire`
--

DROP TABLE IF EXISTS `territoire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `territoire_id` int DEFAULT NULL,
  `territoire_guerre_id` int DEFAULT NULL,
  `appelation_id` int NOT NULL,
  `langue_id` int DEFAULT NULL,
  `religion_id` int DEFAULT NULL,
  `nom` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `capitale` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `politique` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dirigeant` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `population` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `symbole` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `tech_level` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type_racial` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `inspiration` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `armes_predilection` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `vetements` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `noms_masculin` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `noms_feminin` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `frontieres` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `geojson` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `color` varchar(7) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `groupe_id` int DEFAULT NULL,
  `tresor` int DEFAULT NULL,
  `resistance` int DEFAULT NULL,
  `blason` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description_secrete` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `statut` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `culture_id` int unsigned DEFAULT NULL,
  `ordre_social` int NOT NULL,
  `topic_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_zone_politique_zone_politique1_idx` (`territoire_id`),
  KEY `fk_territoire_territoire_guerre1_idx` (`territoire_guerre_id`),
  KEY `fk_territoire_appelation1_idx` (`appelation_id`),
  KEY `fk_territoire_langue1_idx` (`langue_id`),
  KEY `fk_territoire_religion1_idx` (`religion_id`),
  KEY `fk_territoire_groupe1_idx` (`groupe_id`),
  KEY `fk_territoire_culture1_idx` (`culture_id`),
  CONSTRAINT `FK_B8655F542AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F54682CA693` FOREIGN KEY (`territoire_guerre_id`) REFERENCES `territoire_guerre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F547A45358C` FOREIGN KEY (`groupe_id`) REFERENCES `groupe` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F54B108249D` FOREIGN KEY (`culture_id`) REFERENCES `culture` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F54B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F54D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B8655F54F9E65DDB` FOREIGN KEY (`appelation_id`) REFERENCES `appelation` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=455 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_exportation`
--

DROP TABLE IF EXISTS `territoire_exportation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_exportation` (
  `territoire_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ressource_id`),
  KEY `IDX_BC24449DD0F97A8` (`territoire_id`),
  KEY `IDX_BC24449DFC6CD52A` (`ressource_id`),
  CONSTRAINT `FK_BC24449DD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_BC24449DFC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_guerre`
--

DROP TABLE IF EXISTS `territoire_guerre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_guerre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `puissance` int DEFAULT NULL,
  `puissance_max` int DEFAULT NULL,
  `protection` int DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_has_construction`
--

DROP TABLE IF EXISTS `territoire_has_construction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_has_construction` (
  `territoire_id` int NOT NULL,
  `construction_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`construction_id`),
  KEY `IDX_FEB4D8E9D0F97A8` (`territoire_id`),
  KEY `IDX_FEB4D8E9CF48117A` (`construction_id`),
  CONSTRAINT `FK_FEB4D8E9CF48117A` FOREIGN KEY (`construction_id`) REFERENCES `construction` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_FEB4D8E9D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_has_loi`
--

DROP TABLE IF EXISTS `territoire_has_loi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_has_loi` (
  `loi_id` int unsigned NOT NULL,
  `territoire_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`loi_id`),
  KEY `IDX_5470401BAB82AB5` (`loi_id`),
  KEY `IDX_5470401BD0F97A8` (`territoire_id`),
  CONSTRAINT `FK_5470401BAB82AB5` FOREIGN KEY (`loi_id`) REFERENCES `loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5470401BD0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_importation`
--

DROP TABLE IF EXISTS `territoire_importation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_importation` (
  `territoire_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ressource_id`),
  KEY `IDX_77B99CF6D0F97A8` (`territoire_id`),
  KEY `IDX_77B99CF6FC6CD52A` (`ressource_id`),
  CONSTRAINT `FK_77B99CF6D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_77B99CF6FC6CD52A` FOREIGN KEY (`ressource_id`) REFERENCES `ressource` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_ingredient`
--

DROP TABLE IF EXISTS `territoire_ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_ingredient` (
  `territoire_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`ingredient_id`),
  KEY `IDX_9B7BF292D0F97A8` (`territoire_id`),
  KEY `IDX_9B7BF292933FE08C` (`ingredient_id`),
  CONSTRAINT `FK_9B7BF292933FE08C` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredient` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_9B7BF292D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_langue`
--

DROP TABLE IF EXISTS `territoire_langue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_langue` (
  `territoire_id` int NOT NULL,
  `langue_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`langue_id`),
  KEY `IDX_C9327BC3D0F97A8` (`territoire_id`),
  KEY `IDX_C9327BC32AADBACD` (`langue_id`),
  CONSTRAINT `FK_C9327BC32AADBACD` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_C9327BC3D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_quete`
--

DROP TABLE IF EXISTS `territoire_quete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_quete` (
  `territoire_id` int NOT NULL,
  `territoire_cible_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`territoire_cible_id`),
  KEY `IDX_63718DCD0F97A8` (`territoire_id`),
  KEY `IDX_63718DCB011823` (`territoire_cible_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `territoire_religion`
--

DROP TABLE IF EXISTS `territoire_religion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `territoire_religion` (
  `territoire_id` int NOT NULL,
  `religion_id` int NOT NULL,
  PRIMARY KEY (`territoire_id`,`religion_id`),
  KEY `IDX_B23AB2D3D0F97A8` (`territoire_id`),
  KEY `IDX_B23AB2D3B7850CBD` (`religion_id`),
  CONSTRAINT `FK_B23AB2D3B7850CBD` FOREIGN KEY (`religion_id`) REFERENCES `religion` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_B23AB2D3D0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `titre`
--

DROP TABLE IF EXISTS `titre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `renomme` int NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `titre_territoire`
--

DROP TABLE IF EXISTS `titre_territoire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titre_territoire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre_id` int NOT NULL,
  `territoire_id` int NOT NULL,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_titre_territoire_titre1_idx` (`titre_id`),
  KEY `fk_titre_territoire_territoire1_idx` (`territoire_id`),
  CONSTRAINT `FK_FA93160ED0F97A8` FOREIGN KEY (`territoire_id`) REFERENCES `territoire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_FA93160ED54FAE5E` FOREIGN KEY (`titre_id`) REFERENCES `titre` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `token`
--

DROP TABLE IF EXISTS `token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `tag` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `topic`
--

DROP TABLE IF EXISTS `topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `topic` (
  `id` int NOT NULL AUTO_INCREMENT,
  `topic_id` int DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `title` varchar(450) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `creation_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `right` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int DEFAULT NULL,
  `key` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_topic_topic1_idx` (`topic_id`),
  KEY `fk_topic_user1_idx` (`user_id`),
  CONSTRAINT `FK_9D40DE1B1F55203D` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_9D40DE1BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=863 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trigger`
--

DROP TABLE IF EXISTS `trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trigger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnage_id` int NOT NULL,
  `tag` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `done` tinyint(1) NOT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_trigger_idx` (`personnage_id`) USING BTREE,
  CONSTRAINT `FK_1A6B0F5D5E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `etat_civil_id` int DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salt` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `rights` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `isEnabled` tinyint(1) NOT NULL,
  `confirmationToken` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `timePasswordResetRequested` int unsigned DEFAULT NULL,
  `discr` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `trombineUrl` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `lastConnectionDate` datetime DEFAULT NULL,
  `personnage_id` int DEFAULT NULL,
  `coeur` int DEFAULT NULL,
  `personnage_secondaire_id` int DEFAULT NULL,
  `roles` json NOT NULL COMMENT '(DC2Type:json)',
  `is_enabled` tinyint DEFAULT NULL,
  `pwd` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email_contact` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_user_etat_civil1_idx` (`etat_civil_id`),
  KEY `fk_user_personnage_secondaire1_idx` (`personnage_secondaire_id`),
  KEY `fk_user_personnage1_idx` (`personnage_id`),
  CONSTRAINT `FK_8D93D649191476EE` FOREIGN KEY (`etat_civil_id`) REFERENCES `etat_civil` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8D93D6495E315342` FOREIGN KEY (`personnage_id`) REFERENCES `personnage` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_8D93D649E6917FB3` FOREIGN KEY (`personnage_secondaire_id`) REFERENCES `personnage_secondaire` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5791 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_has_restriction`
--

DROP TABLE IF EXISTS `user_has_restriction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_has_restriction` (
  `user_id` int unsigned NOT NULL,
  `restriction_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`restriction_id`),
  KEY `IDX_1A57746A76ED395` (`user_id`),
  KEY `IDX_1A57746E6160631` (`restriction_id`),
  CONSTRAINT `FK_1A57746A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_1A57746E6160631` FOREIGN KEY (`restriction_id`) REFERENCES `restriction` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `watching_user`
--

DROP TABLE IF EXISTS `watching_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `watching_user` (
  `post_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`user_id`),
  KEY `IDX_FFDC43024B89032C` (`post_id`),
  KEY `IDX_FFDC4302A76ED395` (`user_id`),
  CONSTRAINT `FK_FFDC43024B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_FFDC4302A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wt_heroisme_ad`
--

DROP TABLE IF EXISTS `wt_heroisme_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wt_heroisme_ad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `competence_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=660 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wt_litterature_top`
--

DROP TABLE IF EXISTS `wt_litterature_top`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wt_litterature_top` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gn_id` int NOT NULL,
  `personnage_id` int NOT NULL,
  `Compétence` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=770 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-06 20:10:05
