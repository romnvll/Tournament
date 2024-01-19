<?php
require ('security.php');
require ('class/equipeDao.class.php');
require ('class/rencontreDao.class.php');
require ('class/tournoiDao.class.php');
$IdTournoi = $_GET['id_tournoi'];
$equipeDao = new EquipeDAO();

$rencontreDao = new RencontreDAO();
$rencontres = $rencontreDao->getRencontreByTournoi($IdTournoi);

$tournoi = new tournoiDao();
var_dump($tournoi->getTournoiById($IdTournoi));
var_dump($tournoi->getTournoiById($IdTournoi)['PauseEntreDeuxMatchs']);
//var_dump($tournoi->getTournoiById(1)['heure_debut']); 
echo "<pre>";
//print_r($rencontres);
echo "</pre>";

$pasHoraire = $tournoi->getTournoiById($IdTournoi)['pasHoraire'];
//$pasHoraire = 15;
$nbrTerrain = $tournoi->getTournoiById($IdTournoi)['nb_terrains'];

$pauseEntreDeuxMatchs = $tournoi->getTournoiById($IdTournoi)['PauseEntreDeuxMatchs'];
//$pauseEntreDeuxMatchs = 15;
$heure_debut = strtotime("09:00");

// Assuming $rencontres is the array you provided
$categories = array("U17M","U17F","U15F","U17F","U15M","U13M","U11M","U13F","U11M","U11F","MiniConfirme","MiniDebrouillard","MiniDebutant");

$terrains = range(1, $nbrTerrain);

$interval = $pasHoraire * 60; // 15 minutes in seconds

// Specify the time interval during which no team can play
$pause_debut = strtotime("00:00");
$pause_fin = strtotime("01:00");

// Initialize an array to keep track of the number of matches per category
$matches_per_category = array_fill_keys($categories, 0);

// Initialize an array to keep track of the teams' schedules
$schedules = array();

// Initialize an array to keep track of the fields' schedules
$fields_schedules = array();

foreach ($rencontres as &$rencontre) {
    $categorie_index = array_search($rencontre["equipe1_categorie"], $categories);
    if ($categorie_index !== false) {
        if ($matches_per_category[$rencontre["equipe1_categorie"]] < count($terrains)) {
            // Place the first 6 matches of each category at the specified time slots
            $rencontre["num_terrain"] = $terrains[$matches_per_category[$rencontre["equipe1_categorie"]]];
            $rencontre["heure_rencontre"] = date("H:i", $heure_debut + floor($categorie_index / 2) * $interval);
           // $rencontre["heure_rencontre"] = date("H:i", $heure_debut + ($matches_per_category[$rencontre["equipe1_categorie"]] * count($categories) + $categorie_index) * $interval);
        } else {
            // Place the remaining matches in a round-robin fashion
            $rencontre["num_terrain"] = $terrains[($matches_per_category[$rencontre["equipe1_categorie"]] - count($terrains)) % count($terrains)];
            $rencontre["heure_rencontre"] = date("H:i", $heure_debut + (floor($categorie_index / 2) + ceil(($matches_per_category[$rencontre["equipe1_categorie"]] - count($terrains)) / count($terrains))) * $interval);
        }
        // Check if either team is already scheduled to play at the same time or within 15 minutes
        while (isset($schedules[$rencontre["equipe1_id"]][$rencontre["heure_rencontre"]]) || isset($schedules[$rencontre["equipe2_id"]][$rencontre["heure_rencontre"]]) || isset($fields_schedules[$rencontre["num_terrain"]][$rencontre["heure_rencontre"]]) || isset($schedules[$rencontre["equipe1_id"]][date("H:i", strtotime($rencontre["heure_rencontre"]) - $pauseEntreDeuxMatchs * 60)]) || isset($schedules[$rencontre["equipe2_id"]][date("H:i", strtotime($rencontre["heure_rencontre"]) - $pauseEntreDeuxMatchs * 60)])) {
            // If so, reschedule the match to the next available time slot
            $rencontre["heure_rencontre"] = date("H:i", strtotime($rencontre["heure_rencontre"]) + $interval);
        }
        
        // Check if the match is scheduled during the specified time interval
        
        if (strtotime($rencontre["heure_rencontre"]) >= $pause_debut && strtotime($rencontre["heure_rencontre"]) < $pause_fin) {
            // If so, reschedule the match to after the time interval
            $rencontre["heure_rencontre"] = date("H:i", $pause_fin);
        }
        // Update the teams' schedules
$schedules[$rencontre["equipe1_id"]][$rencontre["heure_rencontre"]] = true;
$schedules[$rencontre["equipe2_id"]][$rencontre["heure_rencontre"]] = true;
// Update the fields' schedules
$fields_schedules[$rencontre["num_terrain"]][$rencontre["heure_rencontre"]] = true;
$matches_per_category[$rencontre["equipe1_categorie"]]++;
}
unset($rencontre);
}
// Display the updated array
echo "<pre>";
print_r($rencontres);
echo "</pre>";



foreach ($rencontres as $rencontre) {
    $rencontreDao->modifierRencontre($rencontre['rencontre_id'],null,null,$rencontre['num_terrain'],$rencontre['heure_rencontre'],null,$IdTournoi);
}
