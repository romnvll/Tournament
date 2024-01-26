<?php
require 'security.php';
require ('class/tournoiDao.class.php');
require ('class/rencontreDao.class.php');
require ('class/pouleManagerDao.class.php');
require ('class/equipeDao.class.php');


if ( $_GET['action'] == "delete") {
    $tournoi = $_GET['idTournoi'];
    $tournoiDao = new tournoiDao();
    if ($tournoiDao->getTournoiById($tournoi)['isArchived'] == 0 ) {
        echo "Impossible de supprimer ce tournoi, il n'est pas archivé";

    }

    else {

 


$rencontreDao = new RencontreDAO();
$rencontreDao->supprimerRencontresParTournoi($tournoi);

$pouleDao = new PouleManager();

$pouleDao->supprimerPoulesParTournoi($tournoi);

$equipeDao = new EquipeDAO();
$equipeDao->supprimerEquipesParTournoi($tournoi);


$tournoiDao->supprimerTournoi($tournoi);

header("Location: " . $_SERVER['HTTP_REFERER']);
}




}


?>
