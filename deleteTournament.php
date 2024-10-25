<?php
require 'security.php';
require ('class/tournoiDao.class.php');
require ('class/rencontreDao.class.php');
require ('class/pouleManagerDao.class.php');
require ('class/equipeDao.class.php');
require ('class/arbitreDao.class.php');
require ('class/labelsDao.class.php');
require ('class/creneauxDao.class.php');
require ('class/terrainDao.class.php');
require ('class/planificationDao.class.php');
require ('class/PersonneTableDao.class.php');
require ('class/personneDao.class.php');

if ($_GET['action'] == "delete") {
    $tournoi = $_GET['idTournoi'];
    $tournoiDao = new tournoiDao();

    if ($tournoiDao->getTournoiById($tournoi)['isArchived'] == 0) {
        echo "Impossible de supprimer ce tournoi, il n'est pas archivé";
    } else {
        // Suppression des planifications avant les rencontres
        $planificationDao = new planificationDao();
        try {
            $planificationDao->supprimerPlanificationsParTournoi($tournoi);
            echo "Planifications supprimées pour le tournoi $tournoi";
        } catch (PDOException $e) {
            echo "Erreur lors de la suppression des planifications : " . $e->getMessage();
        }

        // Suppression des rencontres après les planifications
        $rencontreDao = new RencontreDAO();
        try {
            $rencontreDao->supprimerRencontresParTournoi($tournoi);
            echo "Rencontres supprimées pour le tournoi $tournoi";
        } catch (PDOException $e) {
            echo "Erreur lors de la suppression des rencontres : " . $e->getMessage();
        }

        // Suppression des arbitres après les rencontres
        $arbitreDao = new arbitreDao();
        
            $arbitreDao->supprimerArbitresParTournoi($tournoi);
           

        // Suppression des autres entités (équipes, poules, etc.)

        $labelsDao = new labelDao();
        try {
        $labelsDao->supprimerLabelsParTournoi($tournoi);
        echo "label supprimés pour le tournoi  $tournoi <br>";

        }
        catch (PDOException $e) {
            echo "Erreur lors de la suppression des label : " . $e->getMessage();

        }

        $personneDao = new PersonneDao();
        $personneDao->supprimerPersonneParTournoi($tournoi);

        // Suppression des personnes
        $personneTableDao = new PersonneTableDao();
        $personneTableDao->supprimerPersonnesParTournoi($tournoi);

        // Suppression des créneaux
        $creneauxDao = new creneauxDao();
        $creneauxDao->supprimerCreneauxParTournoi($tournoi);

        // Suppression des poules
        $pouleDao = new PouleManager();
        $pouleDao->supprimerPoulesParTournoi($tournoi);

        // Suppression des équipes
        $equipeDao = new EquipeDAO();
        $equipeDao->supprimerEquipesParTournoi($tournoi);

        // Suppression des terrains
        $terrainDao = new TerrainDao();
        $terrainDao->supprimerTerrainsParTournoi($tournoi);

        // Enfin, suppression du tournoi
        try {
            $tournoiDao->supprimerTournoi($tournoi);
            echo "Tournoi supprimé avec succès.";
        } catch (PDOException $e) {
            echo "Erreur lors de la suppression du tournoi : " . $e->getMessage();
        }
    }
}



        
 


header("Location: " . $_SERVER['HTTP_REFERER']);








?>
