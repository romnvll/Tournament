<?php
require 'security.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';

$tournoiDao = new tournoiDao();
$nbrTerrain = $tournoiDao->getNbTerrainsById($_GET['id_tournoi']);



$rencontreDao = new RencontreDAO();
$rencontres = $rencontreDao->getAllRencontresByTournoiId($_GET['id_tournoi']);

function comparerParId($a, $b) {
    return $a['id'] - $b['id'];
}

// Filtrer le tableau $rencontres
//$rencontres = array_filter($rencontres, function($rencontre) {
    //return empty($rencontre['terrain']) && empty($rencontre['heure']);
//});

// Trier le tableau $rencontres par ID
usort($rencontres, 'comparerParId');

// Obtenir l'heure de début et le pas horaire du tournoi
$heure_debut = $tournoiDao->getTournoiById($_GET['id_tournoi'])['heure_debut'];
$pasHoraire = $tournoiDao->getTournoiById($_GET['id_tournoi'])['pasHoraire'];

// Convertir l'heure de début en secondes depuis le début de la journée
list($h, $m) = explode(':', $heure_debut);
$heure_debut_sec = $h * 3600 + $m * 60;

// Créer un tableau pour stocker l'heure de début de chaque terrain
$heures_debut_terrains = array_fill(1, $nbrTerrain, $heure_debut_sec);

// Parcourir le tableau $rencontres et attribuer un terrain et une heure à chaque rencontre
foreach ($rencontres as &$rencontre) {
   
    
    for ($i = 1; $i <= $nbrTerrain; $i++) {
        // Vérifier si 'terrain' et 'heure' sont vides ou null avant de modifier la rencontre
        if (empty($rencontre['terrain']) && empty($rencontre['heure'])) {
            $rencontre['terrain'] = $i;

            // Convertir l'heure de début du terrain en format hh:mm
            $heure = gmdate('H:i', $heures_debut_terrains[$i]);
            
            // Attribuer l'heure à la rencontre
            $rencontre['heure'] = $heure;
            
            // Ajouter le pas horaire à l'heure de début du terrain (en secondes)
            $heures_debut_terrains[$i] += $pasHoraire * 60;
           // echo $heure . "<br> " . $rencontre['terrain'];
            $rencontreDao->modifierRencontre($rencontre['id'],null,null,$rencontre['terrain'],$rencontre['heure'],null,$_GET['id_tournoi']);
            echo $rencontre['heure'] . "-". $rencontre['id'] . "-" . $rencontre['terrain'] . "<br>" ;
            // Une fois qu'un terrain et une heure ont été attribués à la rencontre, sortir de la boucle
            //break;
        }
    }
}


unset($rencontre); // Important : détruire la référence sur le dernier élément

echo "<pre>";
//var_dump($rencontres);
echo "</pre>";




header("Location:RepartitionDesTours.php?id_tournoi=" . $_GET['id_tournoi']);

?>