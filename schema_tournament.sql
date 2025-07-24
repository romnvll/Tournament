/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: tournament2
-- ------------------------------------------------------
-- Server version	10.6.22-MariaDB-0ubuntu0.22.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Arbitres`
--

DROP TABLE IF EXISTS `Arbitres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Arbitres` (
  `arbitre_id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`arbitre_id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  KEY `idx_club_id` (`club_id`),
  CONSTRAINT `Arbitres_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`),
  CONSTRAINT `Arbitres_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `Clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Categorie`
--

DROP TABLE IF EXISTS `Categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Categorie` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `Nom_categorie` varchar(32) NOT NULL,
  `Couleur` varchar(8) NOT NULL,
  `fk_id_club` int(11) NOT NULL,
  PRIMARY KEY (`id_categorie`),
  KEY `idx_fk_id_club` (`fk_id_club`),
  CONSTRAINT `fk_club` FOREIGN KEY (`fk_id_club`) REFERENCES `Clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Clubs`
--

DROP TABLE IF EXISTS `Clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Clubs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `email` varchar(32) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `contact` text NOT NULL,
  `logo` text DEFAULT 'logos/default.png',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Creneaux`
--

DROP TABLE IF EXISTS `Creneaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Creneaux` (
  `creneau_id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` time DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  PRIMARY KEY (`creneau_id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  CONSTRAINT `Creneaux_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=665 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `EquipePoule`
--

DROP TABLE IF EXISTS `EquipePoule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EquipePoule` (
  `equipe_id` int(11) NOT NULL,
  `poule_id` int(11) NOT NULL,
  PRIMARY KEY (`equipe_id`,`poule_id`),
  KEY `poule_id` (`poule_id`),
  KEY `idx_equipepoule_poule_equipe` (`poule_id`,`equipe_id`),
  CONSTRAINT `EquipePoule_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `Equipes` (`id`),
  CONSTRAINT `EquipePoule_ibfk_2` FOREIGN KEY (`poule_id`) REFERENCES `Poules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Equipes`
--

DROP TABLE IF EXISTS `Equipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Equipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `IsPresent` tinyint(1) DEFAULT 0,
  `tournoi_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `categorie` int(11) NOT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  KEY `idx_club_id` (`club_id`),
  KEY `idx_categorie` (`categorie`),
  KEY `idx_equipes_club_id` (`club_id`),
  KEY `idx_equipes_categorie` (`categorie`),
  CONSTRAINT `fk_categorie` FOREIGN KEY (`categorie`) REFERENCES `Categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=508 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Labels`
--

DROP TABLE IF EXISTS `Labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Labels` (
  `label_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `couleur` varchar(16) NOT NULL,
  `tournoi_id` int(11) NOT NULL,
  PRIMARY KEY (`label_id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  CONSTRAINT `Labels_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Personne`
--

DROP TABLE IF EXISTS `Personne`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Personne` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Nom` varchar(32) NOT NULL,
  `Prenom` varchar(32) NOT NULL,
  `Mail` varchar(64) NOT NULL,
  `tournoi_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  CONSTRAINT `fk_tournoi_personne` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PersonneTable`
--

DROP TABLE IF EXISTS `PersonneTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `PersonneTable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personne_id` int(11) NOT NULL,
  `terrain_id` int(11) NOT NULL,
  `code_pin` varchar(255) NOT NULL,
  `url_key` varchar(255) NOT NULL,
  `sentMail` int(1) NOT NULL DEFAULT 0,
  `tournoi_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_personne_terrain` (`personne_id`,`terrain_id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  KEY `idx_terrain_id` (`terrain_id`),
  CONSTRAINT `PersonneTable_ibfk_1` FOREIGN KEY (`personne_id`) REFERENCES `Personne` (`id`),
  CONSTRAINT `PersonneTable_ibfk_2` FOREIGN KEY (`terrain_id`) REFERENCES `Terrains` (`terrain_id`),
  CONSTRAINT `fk_tournoi_personnerencontre` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Planification`
--

DROP TABLE IF EXISTS `Planification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Planification` (
  `planification_id` int(11) NOT NULL AUTO_INCREMENT,
  `terrain_id` int(11) DEFAULT NULL,
  `creneau_id` int(11) DEFAULT NULL,
  `rencontre_id` int(11) DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  `arbitre_id` int(11) DEFAULT NULL,
  `label_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`planification_id`),
  KEY `idx_terrain_id` (`terrain_id`),
  KEY `idx_creneau_id` (`creneau_id`),
  KEY `idx_rencontre_id` (`rencontre_id`),
  KEY `idx_arbitre_id` (`arbitre_id`),
  KEY `idx_label_id` (`label_id`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  KEY `idx_planification_creneau_id` (`creneau_id`),
  CONSTRAINT `Planification_ibfk_1` FOREIGN KEY (`terrain_id`) REFERENCES `Terrains` (`terrain_id`),
  CONSTRAINT `Planification_ibfk_2` FOREIGN KEY (`creneau_id`) REFERENCES `Creneaux` (`creneau_id`),
  CONSTRAINT `Planification_ibfk_3` FOREIGN KEY (`rencontre_id`) REFERENCES `Rencontres` (`id`),
  CONSTRAINT `Planification_ibfk_4` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`),
  CONSTRAINT `Planification_ibfk_5` FOREIGN KEY (`arbitre_id`) REFERENCES `Arbitres` (`arbitre_id`),
  CONSTRAINT `Planification_ibfk_6` FOREIGN KEY (`label_id`) REFERENCES `Labels` (`label_id`)
) ENGINE=InnoDB AUTO_INCREMENT=881 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Poules`
--

DROP TABLE IF EXISTS `Poules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Poules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `is_classement` int(11) NOT NULL DEFAULT 0,
  `fk_idcategorie` int(11) DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fk_idcategorie` (`fk_idcategorie`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  CONSTRAINT `Poules_ibfk_1` FOREIGN KEY (`fk_idcategorie`) REFERENCES `Categorie` (`id_categorie`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Rencontres`
--

DROP TABLE IF EXISTS `Rencontres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rencontres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isClassement` tinyint(1) NOT NULL DEFAULT 0,
  `equipe1_id` int(11) NOT NULL,
  `equipe2_id` int(11) DEFAULT NULL,
  `score1` int(11) DEFAULT NULL,
  `score2` int(11) DEFAULT NULL,
  `tour` int(1) DEFAULT NULL,
  `heure` time DEFAULT NULL,
  `terrain` int(11) DEFAULT NULL,
  `Arbitre` varchar(32) DEFAULT NULL,
  `isTerminated` tinyint(1) NOT NULL DEFAULT 0,
  `tournoi_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_equipe1_id` (`equipe1_id`),
  KEY `idx_equipe2_id` (`equipe2_id`),
  KEY `idx_terrain` (`terrain`),
  KEY `idx_Arbitre` (`Arbitre`),
  KEY `idx_tournoi_id` (`tournoi_id`),
  KEY `idx_rencontres_order` (`tour`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Sponsors`
--

DROP TABLE IF EXISTS `Sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sponsors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `description` text DEFAULT NULL,
  `lien_web` varchar(255) NOT NULL,
  `logo` text DEFAULT 'logos/default_sponsor.png',
  `is_actif` int(1) NOT NULL DEFAULT 1,
  `club_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `Sponsors_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `Clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Terrains`
--

DROP TABLE IF EXISTS `Terrains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Terrains` (
  `terrain_id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(10) NOT NULL,
  `fk_idTournoi` int(11) NOT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`terrain_id`),
  KEY `idx_fk_idTournoi` (`fk_idTournoi`),
  CONSTRAINT `Terrains_ibfk_1` FOREIGN KEY (`fk_idTournoi`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Tournois`
--

DROP TABLE IF EXISTS `Tournois`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tournois` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `dateDebut` date DEFAULT NULL,
  `nb_terrains` int(11) NOT NULL,
  `heure_debut` text NOT NULL,
  `pasHoraire` varchar(11) DEFAULT NULL,
  `gestionRepas` int(1) NOT NULL DEFAULT 0,
  `gestionPartenaires` int(1) NOT NULL DEFAULT 0,
  `gestionTables` int(1) NOT NULL DEFAULT 0,
  `gestionArbitres` int(11) NOT NULL DEFAULT 0,
  `gestionVoix` int(1) NOT NULL DEFAULT 0,
  `isClassement` int(11) DEFAULT NULL,
  `isVisible` int(1) NOT NULL DEFAULT 1,
  `heureIsVisible` int(11) NOT NULL DEFAULT 1,
  `isArchived` int(1) NOT NULL DEFAULT 0,
  `IsRankingView` int(1) NOT NULL DEFAULT 0,
  `refreshClientTime` int(11) NOT NULL DEFAULT 30000,
  `club_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_club_id` (`club_id`),
  CONSTRAINT `fk_tournois_club` FOREIGN KEY (`club_id`) REFERENCES `Clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-24 16:13:11
