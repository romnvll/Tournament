<?php
require 'security.php';
require 'class/tournoiDao.class.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);
//require('class/tournoi.class.php');





$tournoiDao = new tournoiDao();


echo $_POST['dateTournoi'];

$tournoiDao->ajouterTournoi($_POST['nomTournoi'],$_POST['dateTournoi'],$_POST['nbr_terrain'],$_POST['heuredebut'],0,$_POST['pasHoraire']);


$tournoiDao = new tournoiDao();
$tousLesTournois = $tournoiDao->afficherLesTournois();

$dernierId = null;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
    }
}

header("location: modifierTournoi.php?idTournoi=".$dernierId);








?>