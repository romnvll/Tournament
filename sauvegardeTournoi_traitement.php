<?php
require 'security.php';

require 'class/tournoiDao.class.php';
require 'class/TournoiBackupDao.class.php';
require 'class/SauvegardeStockage.class.php';

$tournoiDao = new tournoiDao();
$backupDao = new TournoiBackupDao();
$stockage = new SauvegardeStockage();

/* ---------------------------------------------------------------
 * EXPORT : ?action=export&idTournoi=123
 * Vérifie que le tournoi appartient bien à l'utilisateur (ou admin),
 * enregistre une copie côté serveur, puis force le téléchargement.
 * --------------------------------------------------------------- */
if (($_GET['action'] ?? null) === 'export' && isset($_GET['idTournoi'])) {
    $idTournoi = (int) $_GET['idTournoi'];
    $tournoi = $tournoiDao->getTournoiById($idTournoi);
    
    $estProprietaire = $tournoi && (int) $tournoi['utilisateur_id'] === (int) $userData['id'];
    $estAdmin = $userData['role'] === 'admin';

    if (!$tournoi || (!$estProprietaire && !$estAdmin)) {
        http_response_code(403);
        exit('Accès refusé.');
    }

    try {
        $donnees = $backupDao->exporter($idTournoi);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Erreur lors de la génération de la sauvegarde : ' . htmlspecialchars($e->getMessage()));
    }

    $jsonContenu = json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Copie côté serveur, dans le dossier propre au propriétaire du tournoi (pas forcément l'admin qui exporte)
    $stockage->enregistrer((int) $tournoi['utilisateur_id'], $idTournoi, $tournoi['nom'], $jsonContenu);

    $nomFichierTelechargement = 'tournoi_' . $idTournoi . '_' . date('Ymd_His') . '.json';

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nomFichierTelechargement . '"');
    echo $jsonContenu;
    exit;
}

/* ---------------------------------------------------------------
 * TÉLÉCHARGEMENT d'une sauvegarde déjà stockée sur le serveur :
 * ?action=telecharger&fichier=tournoi_12_....json
 * --------------------------------------------------------------- */
if (($_GET['action'] ?? null) === 'telecharger' && isset($_GET['fichier'])) {
    $contenu = $stockage->lire((int) $userData['id'], $_GET['fichier']);

    if ($contenu === null) {
        http_response_code(404);
        exit('Sauvegarde introuvable.');
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($_GET['fichier']) . '"');
    echo $contenu;
    exit;
}

/* ---------------------------------------------------------------
 * RENOMMAGE d'une sauvegarde stockée : POST action=renommerSauvegarde,
 * fichier=ancien_nom.json, nouveauNom=libellé choisi (sans extension).
 * --------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null) === 'renommerSauvegarde') {
    $nouveauFichier = $stockage->renommer(
        (int) $userData['id'],
        $_POST['fichier'] ?? '',
        $_POST['nouveauNom'] ?? ''
    );

    if ($nouveauFichier === null) {
        header('Location: Sauvegardetournoi.php?status=error&message=' . urlencode('Renommage impossible (nom vide ou déjà utilisé).'));
        exit;
    }

   header('Location: Sauvegardetournoi.php?idTournoi=' . urlencode($_POST['idTournoi'] ?? '') );
    exit;
}

/* ---------------------------------------------------------------
 * SUPPRESSION d'une sauvegarde stockée : ?action=supprimerSauvegarde&fichier=...
 * --------------------------------------------------------------- */
if (($_GET['action'] ?? null) === 'supprimerSauvegarde' && isset($_GET['fichier'])) {
    $stockage->supprimer((int) $userData['id'], $_GET['fichier']);
    header('Location: sauvegardeTournoi.php');
    exit;
}

/* ---------------------------------------------------------------
 * RESTAURATION : POST, soit avec un fichier uploadé ("fichierSauvegarde"),
 * soit en repartant d'une sauvegarde déjà stockée ("fichierStocke").
 * --------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['fichierSauvegarde']) || !empty($_POST['fichierStocke']))) {

    if (!empty($_POST['fichierStocke'])) {
        $contenu = $stockage->lire((int) $userData['id'], $_POST['fichierStocke']);
        if ($contenu === null) {
            header('Location: sauvegardeTournoi.php?status=error&message=' . urlencode('Sauvegarde introuvable sur le serveur.'));
            exit;
        }
    } else {
        $fichier = $_FILES['fichierSauvegarde'];
        if ($fichier['error'] !== UPLOAD_ERR_OK) {
            header('Location: sauvegardeTournoi.php?status=error&message=' . urlencode('Échec du téléversement du fichier.'));
            exit;
        }
        $contenu = file_get_contents($fichier['tmp_name']);
    }

    $donnees = json_decode($contenu, true);

    if (!is_array($donnees) || !isset($donnees['tournoi'])) {
        header('Location: sauvegardeTournoi.php?status=error&message=' . urlencode('Fichier de sauvegarde invalide.'));
        exit;
    }

    try {
        $nouvelIdTournoi = $backupDao->restaurer($donnees, (int) $userData['id']);
    } catch (Throwable $e) {
        header('Location: sauvegardeTournoi.php?status=error&message=' . urlencode('Échec de la restauration : ' . $e->getMessage()));
        exit;
    }

    header('Location: modifierTournoi.php?idTournoi=' . $nouvelIdTournoi . '&status=restored');
    exit;
}

http_response_code(400);
exit('Requête invalide.');