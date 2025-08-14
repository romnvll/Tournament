<?php
// Sécurité basique
$idTournoi = isset($_POST['idTournoi']) ? (int)$_POST['idTournoi'] : 0;
$idEquipe = isset($_POST['idEquipe']) ? (int)$_POST['idEquipe'] : 0;
$idTerrain = isset($_POST['idTerrain']) ? (int)$_POST['idTerrain'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (!isset($_FILES['audio']) && !isset($_FILES['audio_data'])) {
    http_response_code(400);
    echo "Aucun fichier audio envoyé";
    exit;
}

if ($type === 'arbitre') {
    // Gestion spécifique pour arbitre
    $idArbitre = isset($_POST['idArbitre']) ? (int)$_POST['idArbitre'] : 0;
    if ($idTournoi <= 0 || $idArbitre <= 0 || !isset($_FILES['audio_data'])) {
        http_response_code(400);
        echo "Paramètres arbitre manquants ou incorrects";
        exit;
    }

    $dossier = "Audio/$idTournoi/Arbitres";
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    $fichierAudio = "$dossier/$idArbitre.wav";
    if (move_uploaded_file($_FILES['audio_data']['tmp_name'], $fichierAudio)) {
        // Mise à jour en BDD
        require_once 'class/arbitreDao.class.php';
        $dao = new arbitreDao();
        $dao->updateAudioPath($idArbitre, $fichierAudio);
        http_response_code(200);
        echo "Audio arbitre enregistré : $fichierAudio";
    } else {
        http_response_code(500);
        echo "Échec de l’enregistrement du fichier arbitre";
    }

    // On s'arrête ici pour éviter de continuer avec les autres traitements
    exit;
}

// Si ce n'est pas un arbitre, on continue normalement
if ($idTournoi <= 0 || ($idEquipe <= 0 && $idTerrain <= 0)) {
    http_response_code(400);
    echo "Paramètres manquants ou incorrects";
    exit;
}

// Extension et vérification
$extension = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
$allowed = ['wav', 'mp3', 'ogg'];
if (!in_array($extension, $allowed)) {
    http_response_code(415); // Unsupported Media Type
    echo "Extension non autorisée";
    exit;
}

// Déterminer chemin relatif
if ($idEquipe > 0) {
    $relativePath = "Audio/$idTournoi/Equipe/$idEquipe.$extension";
} else {
    $relativePath = "Audio/$idTournoi/Terrain/$idTerrain.$extension";
}

// Déterminer chemin absolu
$absolutePath = __DIR__ . '/' . $relativePath;
$directory = dirname($absolutePath);

// Créer le répertoire si nécessaire
if (!is_dir($directory)) {
    if (!mkdir($directory, 0777, true)) {
        http_response_code(500);
        echo "Erreur lors de la création du répertoire";
        exit;
    }
}

// Écraser ou déplacer le fichier
if (move_uploaded_file($_FILES['audio']['tmp_name'], $absolutePath)) {
    // Enregistrement en base
    if ($idEquipe > 0) {
        require_once 'class/equipeDao.class.php';
        $equipeDao = new EquipeDAO();
        $equipeDao->mettreAJourAudioEquipe($idEquipe, $relativePath);
    } elseif ($idTerrain > 0) {
        require_once 'class/terrainDao.class.php';
        $terrainDao = new TerrainDAO();
        $terrainDao->mettreAJourAudioTerrain($idTerrain, $relativePath);
    }

    http_response_code(200);
    echo "Audio enregistré avec succès";
} else {
    http_response_code(500);
    echo "Échec de l’enregistrement du fichier";
}
