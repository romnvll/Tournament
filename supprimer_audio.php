<?php

require_once 'security.php';
require_once 'vendor/autoload.php';
require_once 'class/terrainDao.class.php';
require_once 'class/arbitreDao.class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Sans "type", on considère qu'il s'agit d'un terrain (ancien comportement)
$type      = $_POST['type'] ?? 'terrain';
$idTournoi = (int) ($_POST['idTournoi'] ?? 0);
$idTerrain = (int) ($_POST['idTerrain'] ?? 0);
$idArbitre = (int) ($_POST['idArbitre'] ?? 0);

if ($idTournoi <= 0 || !in_array($type, ['terrain', 'arbitre'], true)) {
    http_response_code(400);
    exit;
}

/**
 * Supprime un fichier uniquement s'il se trouve bien dans le dossier Audio.
 * Retourne false en cas d'échec de suppression.
 */
function supprimerFichierAudio(string $cheminRelatif): bool
{
    $racineAudio = realpath(__DIR__ . '/Audio');
    $fichier     = realpath(__DIR__ . '/' . ltrim($cheminRelatif, '/'));

    // Fichier absent ou hors du dossier Audio : rien à supprimer
    if ($fichier === false || $racineAudio === false
        || strpos($fichier, $racineAudio . DIRECTORY_SEPARATOR) !== 0
        || !is_file($fichier)) {
        return true;
    }

    return unlink($fichier);
}

if ($type === 'terrain') {

    if ($idTerrain <= 0) {
        http_response_code(400);
        exit;
    }

    // Chemin reconstruit côté serveur à partir d'entiers
    $fichier = __DIR__ . "/Audio/{$idTournoi}/Terrain/{$idTerrain}.wav";

    if (is_file($fichier) && !unlink($fichier)) {
        http_response_code(500);
        exit;
    }

    $terrainDao = new terrainDao();
    $terrainDao->mettreAJourAudioTerrain($idTerrain, null);

} else { // arbitre

    if ($idArbitre <= 0) {
        http_response_code(400);
        exit;
    }

    $arbitreDao = new arbitreDao();

    // Le chemin vient de la base, jamais du client
    $cheminRelatif = $arbitreDao->getAudioPathArbitre($idArbitre);

    if ($cheminRelatif && !supprimerFichierAudio($cheminRelatif)) {
        http_response_code(500);
        exit;
    }

    $arbitreDao->updateAudioPath($idArbitre, null);
}

http_response_code(200);