<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/tournoi.class.php');




$terrainDao=new TerrainDao();
$tournoiDao = new tournoiDao();


echo $_POST['dateTournoi'];
var_dump($_POST);
$tournoiDao->ajouterTournoi($_POST['nomTournoi'],$_POST['dateTournoi'],1,$_POST['heuredebut'],0,$_POST['pasHoraire']);


$tournoiDao = new tournoiDao();
$tousLesTournois = $tournoiDao->afficherLesTournois();

$dernierId = null;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
    }
}

$nbrterrain = $_POST['nbrterrain'] + 1;
for ($i=1; $i<$nbrterrain;$i++){
$terrainDao->ajoutTerrain($dernierId,$i);
}


header("location: modifierTournoi.php?idTournoi=".$dernierId);








?>