<?php
// getScores.php
header('Content-Type: application/json');
require ('class/rencontreDao.class.php'); // Assurez-vous que le chemin est correct
$idTournoi = $_GET['idTournoi'];

// Ta requête SQL pour récupérer les scores
$scores = []; // [rencontre_id => [score1, score2, isTerminated], ...]


$rencontreDao = new RencontreDao(); // Créez une instance de votre classe RencontreDao  
$rencontres = $rencontreDao->getAllRencontresByTournoiId($idTournoi); // Appel de la méthode pour récupérer les scores
 // Affiche le contenu de $rencontres pour le débogage


foreach ($rencontres as $row) {
    $scores[$row['id']] = $row;
}

echo json_encode($scores);