<?php
/**
 * delete_audio.php
 * Supprime le fichier WAV d'une équipe et met à jour la BDD si nécessaire.
 */

header('Content-Type: application/json');

// Vérifie que la requête est bien en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$idTournoi = isset($_POST['idTournoi']) ? (int) $_POST['idTournoi'] : 0;
$idEquipe  = isset($_POST['idEquipe'])  ? (int) $_POST['idEquipe']  : 0;

if ($idTournoi <= 0 || $idEquipe <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Chemin du fichier audio
$audioPath = __DIR__ . "/Audio/{$idTournoi}/Equipe/{$idEquipe}.wav";

// Vérifie que le fichier existe et est bien dans le dossier autorisé (sécurité)
$realAudioPath  = realpath($audioPath);
$allowedBaseDir = realpath(__DIR__ . '/Audio');

if ($realAudioPath === false || strpos($realAudioPath, $allowedBaseDir) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Fichier introuvable ou accès refusé']);
    exit;
}

// Suppression du fichier
if (unlink($realAudioPath)) {
     require_once 'class/equipeDao.class.php';
        $equipeDao = new EquipeDAO();
        $equipeDao->mettreAJourAudioEquipe($idEquipe, null);
  

    echo json_encode(['success' => true, 'message' => 'Enregistrement supprimé']);
} else {
    echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le fichier']);
}
