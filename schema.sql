-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: tournament
-- ------------------------------------------------------
-- Server version	10.6.12-MariaDB-0ubuntu0.22.04.1

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
-- Table structure for table `Clubs`
--

DROP TABLE IF EXISTS `Clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Clubs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `email` varchar(32) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `contact` text NOT NULL,
  `logo` text DEFAULT 'logos/default.png',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `EquipePoule`
--

DROP TABLE IF EXISTS `EquipePoule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EquipePoule` (
  `equipe_id` int(11) NOT NULL,
  `poule_id` int(11) NOT NULL,
  PRIMARY KEY (`equipe_id`,`poule_id`),
  KEY `poule_id` (`poule_id`),
  CONSTRAINT `EquipePoule_ibfk_1` FOREIGN KEY (`equipe_id`) REFERENCES `Equipes` (`id`),
  CONSTRAINT `EquipePoule_ibfk_2` FOREIGN KEY (`poule_id`) REFERENCES `Poules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Equipes`
--

DROP TABLE IF EXISTS `Equipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Equipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `categorie` text NOT NULL,
  `IsPresent` tinyint(1) DEFAULT 1,
  `tournoi_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tournoi_id` (`tournoi_id`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `Equipes_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`),
  CONSTRAINT `Equipes_ibfk_3` FOREIGN KEY (`club_id`) REFERENCES `Clubs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Poules`
--

DROP TABLE IF EXISTS `Poules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Poules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `is_classement` int(11) NOT NULL DEFAULT 0,
  `categorie` text NOT NULL,
  `tournoi_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tournoi_id` (`tournoi_id`),
  CONSTRAINT `Poules_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Rencontres`
--

DROP TABLE IF EXISTS `Rencontres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `tournoi_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipe1_id` (`equipe1_id`),
  KEY `equipe2_id` (`equipe2_id`),
  KEY `fk_tournoi_id` (`tournoi_id`),
  CONSTRAINT `Rencontres_ibfk_1` FOREIGN KEY (`equipe1_id`) REFERENCES `Equipes` (`id`),
  CONSTRAINT `Rencontres_ibfk_2` FOREIGN KEY (`equipe2_id`) REFERENCES `Equipes` (`id`),
  CONSTRAINT `fk_tournoi_id` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1745 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Tournois`
--

DROP TABLE IF EXISTS `Tournois`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Tournois` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `nb_terrains` int(11) NOT NULL,
  `heure_debut` text NOT NULL,
  `heure_fin` text NOT NULL,
  `pasHoraire` varchar(11) DEFAULT NULL,
  `isClassement` int(11) DEFAULT NULL,
  `IdParent` int(11) DEFAULT NULL,
  `isVisible` int(1) NOT NULL DEFAULT 1,
  `heureIsVisible` int(11) NOT NULL DEFAULT 1,
  `isArchived` int(1) NOT NULL DEFAULT 0,
  `IsRankingView` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-01-22 11:58:59
