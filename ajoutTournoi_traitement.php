<?php
require 'security.php';
require 'class/tournoiDao.class.php';
require 'class/terrainDao.class.php';
require 'class/labelsDao.class.php';
require 'class/creneauxDao.class.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);

var_dump($_POST['heuredebut']);



$terrainDao=new TerrainDao();
$tournoiDao = new tournoiDao();
$creneauxDao = new CreneauxDao();


//echo $_POST['dateTournoi'];

$ajoutTournoi = $tournoiDao->ajouterTournoi($_POST['nomTournoi'],$_POST['dateTournoi'],1,$_POST['heuredebut'],0,$userData['id'],$_POST['pasHoraire']);




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

//création des creneaux
$heureDebut = DateTime::createFromFormat('H:i', $_POST['heuredebut']);
$pasHoraire = (int) $_POST['pasHoraire']; // Assure-toi que c'est bien un entier

$creneauxDao->ajouterCreneau($heureDebut->format('H:i'), $dernierId);

for ($i = 1; $i <= 20; $i++) {
    // Ajouter l'intervalle de temps
    $heureDebut->add(new DateInterval("PT{$pasHoraire}M"));
    
    // Insérer le nouveau créneau
    $creneauxDao->ajouterCreneau($heureDebut->format('H:i'), $dernierId);
}


$labelsDao = new LabelDao();
$labelsDao->ajouterLabel("Pause","#000000",$dernierId);

header("location: modifierTournoi.php?idTournoi=".$dernierId);








?>