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

if ( $_GET['action'] == "delete") {
    $tournoi = $_GET['idTournoi'];
    $tournoiDao = new tournoiDao();
    if ($tournoiDao->getTournoiById($tournoi)['isArchived'] == 0 ) {
        echo "Impossible de supprimer ce tournoi, il n'est pas archivé";

    }

    else {

 $planificationDao = new planificationDao();
 $planificationDao->supprimerPlanificationsParTournoi($tournoi);

 $rencontreDao = new RencontreDAO();
$rencontreDao->supprimerRencontresParTournoi($tournoi);


$labelsDao = new labelDao();
$labelsDao->supprimerLabelsParTournoi($tournoi);
        
$arbitreDao = new arbitreDao();
$arbitreDao->supprimerArbitre($tournoi);



$personneTableDao = new PersonneTableDao();
$personneTableDao->supprimerPersonnesParTournoi($tournoi);

$personneDao = new PersonneDao();
$personneDao->supprimerPersonneParTournoi($tournoi);

$creneaux = new creneauxDao();
$creneaux->supprimerCreneauxParTournoi($tournoi);


$pouleDao = new PouleManager();
$pouleDao->supprimerPoulesParTournoi($tournoi);

$equipeDao = new EquipeDAO();
$equipeDao->supprimerEquipesParTournoi($tournoi);

$terrainDao = new TerrainDao();
$terrainDao->supprimerTerrainsParTournoi($tournoi);








$tournoiDao->supprimerTournoi($tournoi);

header("Location: " . $_SERVER['HTTP_REFERER']);
}




}


?>
