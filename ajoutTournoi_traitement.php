<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
require 'class/labelsDao.class.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/tournoi.class.php');




$terrainDao=new TerrainDao();
$tournoiDao = new tournoiDao();


//echo $_POST['dateTournoi'];

$ajoutTournoi = $tournoiDao->ajouterTournoi($_POST['nomTournoi'],$_POST['dateTournoi'],1,$_POST['heuredebut'],0,$_POST['pasHoraire'],$userData['id']);




$tournoiDao = new tournoiDao();
$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);

$dernierId = null;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
    }
}


for ($i=1; $i<=$_POST['nbrterrain'];$i++){

$terrainDao->ajoutTerrain($ajoutTournoi,$i);

}

$labelsDao = new LabelDao();
$labelsDao->ajouterLabel("Pause","#000000",$dernierId);

header("location: modifierTournoi.php?idTournoi=".$dernierId);








?>