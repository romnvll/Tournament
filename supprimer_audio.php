<?php

require_once 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/terrainDao.class.php';
// + ta classe de connexion / DAO terrains

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$idTournoi = (int) ($_POST['idTournoi'] ?? 0);
$idTerrain = (int) ($_POST['idTerrain'] ?? 0);

if ($idTournoi <= 0 || $idTerrain <= 0) {
    http_response_code(400);
    exit;
}

// Chemin reconstruit côté serveur à partir d'entiers : rien venant du client n'est utilisé tel quel
$fichier = __DIR__ . "/Audio/{$idTournoi}/Terrain/{$idTerrain}.wav";

if (is_file($fichier) && !unlink($fichier)) {
    http_response_code(500);
    exit;
}

// Vide audio_path en base (adapte au nom de ta connexion PDO)

$terrainDao = new terrainDao();
$terrainDao->mettreAJourAudioTerrain($idTerrain, null);
http_response_code(200);