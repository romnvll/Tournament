<?php
require 'security.php';
require 'class/tournoiDao.class.php';

// ─────────────────────────────────────────────
// 1. Résolution de l'ID du tournoi
// ─────────────────────────────────────────────
if (isset($_POST['idTournoi']) && is_numeric($_POST['idTournoi'])) {
    $tournoiId = (int)$_POST['idTournoi'];
} elseif (isset($_GET['tournoiId']) && is_numeric($_GET['tournoiId'])) {
    $tournoiId = (int)$_GET['tournoiId'];
} else {
    exit;
}

$tournoiDao = new tournoiDao();

// Vérification des droits
if ($tournoiDao->droitTournoiClub($tournoiId, $userData['id']) === null) {
    exit;
}

// ─────────────────────────────────────────────
// 2. Actions GET spécifiques
// ─────────────────────────────────────────────

// --- Affecter une personne à un terrain ---
if (isset($_GET['action']) && $_GET['action'] === 'ajoutUserSurTable') {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    try {
        $personneTable->genererUrlEtCodePin(
            (int)$_GET['idPersonne'],
            (int)$_GET['idterrain'],
            $tournoiId
        );
    } catch (Exception $e) {
        // On redirige même en cas d'erreur
    }
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
    exit;
}

// --- Supprimer une personne d'un terrain ---
if (isset($_GET['action']) && $_GET['action'] === 'delPersonneTable') {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $personneTable->supprimerPersonneTable((int)$_GET['personneTableId']);
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
    exit;
}

// --- Envoyer un mail à une personne terrain ---
if (isset($_GET['action']) && $_GET['action'] === 'sendMail') {
    require 'class/PersonneTableDao.class.php';
    $personneTable = new PersonneTableDao();
    $status = $personneTable->envoyerMail((int)$_GET['personneTableId']);
    $statusParam = $status ? 'success' : 'error';
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&status={$statusParam}&#placementPersonneSurTerrain&tab=gestionTables");
    exit;
}

// --- Ajouter une personne ---
if (isset($_GET['addPersonne']) && $_GET['addPersonne'] === 'true') {
    require 'class/personneDao.class.php';
    $personne = new PersonneDao();
    $personne->ajouterPersonne(
        $_GET['nom'],
        $_GET['prenom'],
        $_GET['mail'],
        $tournoiId
    );
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
    exit;
}

// --- Supprimer une personne ---
if (isset($_GET['action']) && $_GET['action'] === 'delPersonne') {
    require 'class/personneDao.class.php';
    $personne = new PersonneDao();
    $personne->supprimerPersonne(
        (int)$_GET['idPersonne'],
        $tournoiId,
        $userData['id']
    );
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
    exit;
}

// --- Ajouter un arbitre ---
if (isset($_GET['addArbitre']) && $_GET['addArbitre'] === 'true') {
    require 'class/arbitreDao.class.php';
    $arbitre = new arbitreDao();
    $arbitre->ajouterArbitre($_GET['nomArbitre'], $tournoiId, (int)$_GET['clubID']);
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=arbitres");
    exit;
}

// --- Supprimer un arbitre ---
if (isset($_GET['delArbitre']) && $_GET['delArbitre'] === 'true') {
    require 'class/arbitreDao.class.php';
    $arbitre = new arbitreDao();
    try {
        $arbitre->supprimerArbitre((int)$_GET['arbitre_id']);
        header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=arbitres");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Erreur : impossible de supprimer cet arbitre car il est encore associé à une planification.";
            echo "<script>setTimeout(function(){ window.location.href = '" . $_SERVER['HTTP_REFERER'] . "'; }, 5000);</script>";
        } else {
            echo "Erreur lors de la suppression de l'arbitre : " . $e->getMessage();
        }
    }
    exit;
}

// ─────────────────────────────────────────────
// 3. Actions POST spécifiques
// ─────────────────────────────────────────────

// --- Attacher / détacher un gymnase ---
if (isset($_POST['attacherGymnase'])) {
    require_once 'class/gymnaseDao.class.php';
    $gymnaseDao = new GymnaseDAO();
    $gymnase_id = !empty($_POST['gymnase_id']) ? (int)$_POST['gymnase_id'] : null;
    $gymnaseDao->attacherGymnaseATournoi($tournoiId, $userData['id'], $gymnase_id);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// --- Modifier le nom d'un arbitre (HTMX) ---
if (isset($_POST['idArbitre'])) {
    require 'class/arbitreDao.class.php';
    $arbitreDao = new arbitreDao();
    $arbitreDao->modifierArbitre((int)$_POST['idArbitre'], $_POST['nomArbitre'] ?? '');
    echo "✅ Ok!";
    exit;
}

// ─────────────────────────────────────────────
// 4. Mise à jour générale du tournoi (formulaire principal)
// ─────────────────────────────────────────────

// Booléens — on utilise isset() pour éviter les faux positifs sur == ""
$isArchived         = isset($_POST['isArchived'])         ? 1 : 0;
$heureIsVisible     = isset($_POST['heureIsVisible'])     ? 1 : 0;
$isVisible          = isset($_POST['isVisible'])          ? 1 : 0;
$IsRankingView      = isset($_POST['IsRankingView'])      ? 1 : 0;
$gestionTables      = isset($_POST['gestionTables'])      ? 1 : 0;
$gestionArbitres    = isset($_POST['gestionArbitres'])    ? 1 : 0;
$gestionVoix        = isset($_POST['gestionVoix'])        ? 1 : 0;
$gestionPartenaires = isset($_POST['gestionPartenaires']) ? 1 : 0;
$gestionInformations= isset($_POST['gestionInformations'])? 1 : 0;
$gestionRepas       = isset($_POST['gestionRepas'])       ? 1 : 0;

// isClassement — champ idParent absent du formulaire, on sécurise
$isClassement = (!empty($_POST['idParent'])) ? 1 : 0;

// Effets sonores
$tournoiDao->toggleEffetsSonores($tournoiId, isset($_POST['effetsSonores']) ? 1 : 0);

// refreshClientTime — si absent (soumission d'un autre formulaire), on récupère
// la valeur actuelle en base pour ne pas l'écraser avec 0
if (isset($_POST['refreshClientTime']) && $_POST['refreshClientTime'] !== '') {
    $tempRefresh = (int)$_POST['refreshClientTime'] * 1000;
} else {
    $infoActuelle = $tournoiDao->getTournoiById($tournoiId); // à adapter selon votre DAO
    $tempRefresh  = (int)($infoActuelle['refreshClientTime'] ?? 30000);
}

// Mise à jour
$tournoiDao->modifierTournoi(
    $tournoiId,
    $_POST['nom']          ?? '',
    $_POST['heure_debut']  ?? '',
    $isClassement,
    $_POST['pasHoraire']   ?? 0,
    $isVisible,
    $heureIsVisible,
    $isArchived,
    $IsRankingView,
    $gestionTables,
    $gestionArbitres,
    $tempRefresh,
    $gestionRepas,
    $gestionPartenaires,
    $gestionVoix,
    $gestionInformations
);

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;