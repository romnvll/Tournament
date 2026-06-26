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

if (isset($_GET['action'])) {
    switch ($_GET['action']) {

        case 'ajoutUserSurTable':
            require 'class/PersonneTableDao.class.php';
            $personneTable = new PersonneTableDao();
            try {
                $personneTable->genererUrlEtCodePin(
                    (int)$_GET['idPersonne'],
                    (int)$_GET['idterrain'],
                    $tournoiId
                );
            } catch (Exception $e) {}
            header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
            exit;

        case 'delPersonneTable':
            require 'class/PersonneTableDao.class.php';
            $personneTable = new PersonneTableDao();
            $personneTable->supprimerPersonneTable((int)$_GET['personneTableId']);
            header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
            exit;

        case 'sendMail':
            require 'class/PersonneTableDao.class.php';
            $personneTable = new PersonneTableDao();
            $status = $personneTable->envoyerMail((int)$_GET['personneTableId']);
            $statusParam = $status ? 'success' : 'error';
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&status={$statusParam}&#placementPersonneSurTerrain&tab=gestionTables");
            exit;

        case 'delPersonne':
            require 'class/personneDao.class.php';
            $personne = new PersonneDao();
            $personne->supprimerPersonne(
                (int)$_GET['idPersonne'],
                $tournoiId,
                $userData['id']
            );
            header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=gestionTables");
            exit;

        case 'delArbitre':
            
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
}

// --- Modifier téléphone / commentaire du tournoi ---
if (isset($_POST['actionContact'])) {
    $telephone = trim($_POST['telephone'] ?? '') ?: null;
    $commentaire = trim($_POST['commentaire'] ?? '') ?: null;

    $tournoiDao->modifierTelephoneCommentaire($tournoiId, $telephone, $commentaire);

    header("Location: " . $_SERVER['HTTP_REFERER']);
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

// --- Ajouter un arbitre ---
if (isset($_GET['addArbitre']) && $_GET['addArbitre'] === 'true') {
    require 'class/arbitreDao.class.php';

    if (empty($_GET['clubID'])) {
        header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=arbitres&error=Il faut au moins créer les équipes avant de placer les arbitres");
        exit;
    }

    $arbitre = new arbitreDao();
    $arbitre->ajouterArbitre($_GET['nomArbitre'], $tournoiId, (int)$_GET['clubID']);
    header("Location: modifierTournoi.php?idTournoi={$tournoiId}&tab=arbitres");
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

// --- Archiver / désarchiver un tournoi ---      ← ICI, au bon niveau
if (isset($_POST['actionArchiver'])) {
    $isArchived   = isset($_POST['isArchived']) ? 1 : 0;
    $infoActuelle = $tournoiDao->getTournoiById($tournoiId);

    $tournoiDao->modifierTournoi(
        $tournoiId,
        $infoActuelle['nom'],
        $infoActuelle['heure_debut'],
        $infoActuelle['isClassement'],
        $infoActuelle['pasHoraire'],
        $infoActuelle['isVisible'],
        $infoActuelle['heureIsVisible'],
        $isArchived,
        $infoActuelle['IsRankingView'],
        $infoActuelle['gestionTables'],
        $infoActuelle['gestionArbitres'],
        $infoActuelle['refreshClientTime'],
        $infoActuelle['gestionRepas'],
        $infoActuelle['gestionPartenaires'],
        $infoActuelle['gestionVoix'],
        $infoActuelle['gestionInformations']
    );

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// --- Modifier nom/prénom/mail d'une personne table (inline) ---
if (isset($_POST['idPersonneTable'])) {
    require 'class/personneDao.class.php';
    $personneDao = new PersonneDao();
    $personneDao->modifierPersonne(
        (int)$_POST['idPersonneTable'],
        trim($_POST['nomPersonneTable']    ?? ''),
        trim($_POST['prenomPersonneTable'] ?? ''),
        trim($_POST['mailPersonneTable']   ?? ''),
        $tournoiId
    );
    echo "✅ Ok!";
    exit;
}


// ─────────────────────────────────────────────
// 4. Mise à jour générale du tournoi (formulaire principal)
// ─────────────────────────────────────────────

if (isset($_POST['nom'])) {
    $gestionTempsChangement =    isset($_POST['gestionTempsChangement']) ? 1 : 0;
    $afficherCountdownCoach = isset($_POST['afficherCountdownCoach']) ? 1 : 0; // ← ajouté
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

    $isClassement = (!empty($_POST['idParent'])) ? 1 : 0;

        $tournoiDao->gestionTempChangement(
    $tournoiId,
    $gestionTempsChangement
);
if (isset($_POST['tempsChangementMinutes'])) {
    $tournoiDao->modifierTempsChangement(
        $tournoiId,
        (int)$_POST['tempsChangementMinutes']
    );
}
                $tournoiDao->toggleAfficherCountdownCoach($tournoiId, $afficherCountdownCoach); // ← ajouté

    $tournoiDao->toggleEffetsSonores($tournoiId, isset($_POST['effetsSonores']) ? 1 : 0);

    if (isset($_POST['refreshClientTime']) && $_POST['refreshClientTime'] !== '') {
        $tempRefresh = (int)$_POST['refreshClientTime'] * 1000;
    } else {
        $infoActuelle = $tournoiDao->getTournoiById($tournoiId);
        $tempRefresh  = (int)($infoActuelle['refreshClientTime'] ?? 30000);
    }

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
}