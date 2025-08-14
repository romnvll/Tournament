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
  `utilisateur_id` int(11) NOT NULL,
  PRIMARY KEY (`id_categorie`),
  KEY `fk_categorie_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_categorie_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `logo` text DEFAULT 'logos/default.png',
  `type_sport_id` int(11) NOT NULL DEFAULT 1,
  `utilisateur_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_clubs_type_sport` (`type_sport_id`),
  KEY `fk_club_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_club_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_clubs_type_sport` FOREIGN KEY (`type_sport_id`) REFERENCES `TypeDeSport` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  KEY `idx_tournoi_id` (`tournoi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=918 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  CONSTRAINT `EquipePoule_ibfk_2` FOREIGN KEY (`poule_id`) REFERENCES `Poules` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
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
  CONSTRAINT `fk_categorie` FOREIGN KEY (`categorie`) REFERENCES `Categorie` (`id_categorie`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=567 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  CONSTRAINT `Labels_ibfk_1` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Licence`
--

DROP TABLE IF EXISTS `Licence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Licence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `licence_type_id` int(11) NOT NULL DEFAULT 1,
  `date_debut` date NOT NULL DEFAULT curdate(),
  `date_fin` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `licence_type_id` (`licence_type_id`),
  KEY `Licence_ibfk_1` (`utilisateur_id`),
  CONSTRAINT `Licence_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Licence_ibfk_3` FOREIGN KEY (`licence_type_id`) REFERENCES `LicenceType` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LicenceType`
--

DROP TABLE IF EXISTS `LicenceType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `LicenceType` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prix` decimal(8,2) NOT NULL,
  `limite_tournois` int(11) NOT NULL,
  `limite_equipes` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `duree_jours` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  CONSTRAINT `Planification_ibfk_4` FOREIGN KEY (`tournoi_id`) REFERENCES `Tournois` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `Planification_ibfk_5` FOREIGN KEY (`arbitre_id`) REFERENCES `Arbitres` (`arbitre_id`),
  CONSTRAINT `Planification_ibfk_6` FOREIGN KEY (`label_id`) REFERENCES `Labels` (`label_id`)
) ENGINE=InnoDB AUTO_INCREMENT=923 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  CONSTRAINT `Poules_ibfk_1` FOREIGN KEY (`fk_idcategorie`) REFERENCES `Categorie` (`id_categorie`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `telephone` varchar(15) DEFAULT NULL,
  `adresse` varchar(200) DEFAULT NULL,
  `is_actif` int(1) NOT NULL DEFAULT 1,
  `utilisateur_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sponsors_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_sponsors_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  CONSTRAINT `Terrains_ibfk_1` FOREIGN KEY (`fk_idTournoi`) REFERENCES `Tournois` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `type_sport_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) NOT NULL DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `Tournois_ibfk_1` (`utilisateur_id`),
  KEY `fk_tournoi_type_sport` (`type_sport_id`),
  CONSTRAINT `Tournois_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_tournoi_type_sport` FOREIGN KEY (`type_sport_id`) REFERENCES `TypeDeSport` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tournois_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_utilisateur_id` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TypeDeSport`
--

DROP TABLE IF EXISTS `TypeDeSport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TypeDeSport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Utilisateurs`
--

DROP TABLE IF EXISTS `Utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(64) NOT NULL,
  `password` text NOT NULL,
  `nom` varchar(64) DEFAULT NULL,
  `prenom` varchar(64) DEFAULT NULL,
  `role` enum('admin','organisateur','arbitre','club') NOT NULL DEFAULT 'organisateur',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_token` varchar(255) DEFAULT NULL,
  `email_confirme` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-13  9:14:56
