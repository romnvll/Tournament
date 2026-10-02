<?php
require 'security.php';

require 'class/tournoiDao.class.php';
$tournoiDao = new tournoiDao();



// Récupère les données JSON envoyées par fetch
$data = json_decode(file_get_contents('php://input'), true);

if ($tournoiDao->droitTournoiClub($data['idTournoi'], $userData['id']) == null) {
      
    exit;
  }


if (!isset($data['idTournoi'])) {
    http_response_code(400);
    echo "ID du tournoi manquant";
    exit;
}

$idTournoi = (int)$data['idTournoi'];

// Chemins des fichiers à supprimer
$pngFile = __DIR__ . "/img/planTournoi/{$idTournoi}-plan.png";
$jsonFile = __DIR__ . "/img/planTournoi/{$idTournoi}-plan.json";

$errors = [];

// Supprimer PNG
if (file_exists($pngFile)) {
    if (!unlink($pngFile)) {
        $errors[] = "Impossible de supprimer le fichier PNG";
    }
}

// Supprimer JSON
if (file_exists($jsonFile)) {
    if (!unlink($jsonFile)) {
        $errors[] = "Impossible de supprimer le fichier JSON";
    }
}

if (empty($errors)) {
    echo "Le plan du tournoi a été supprimé avec succès.";
} else {
    http_response_code(500);
    echo implode(" | ", $errors);
}
