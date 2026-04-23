<?php
require('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/planificationDao.class.php';
require 'class/licenceDao.class.php';
require 'Lang/lang.php';

// ─── Bootstrap Twig ───────────────────────────────────────────────────────────
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));

// ─── DAOs ─────────────────────────────────────────────────────────────────────
$tournoiDao     = new tournoiDao();
$pouleManager   = new PouleManager();
$equipeDao      = new EquipeDAO();
$rencontreDao   = new RencontreDAO();
$licenceDao     = new LicenceDao();

// ─── Sécurité / tournoi obligatoire ──────────────────────────────────────────
$idTournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
if ($idTournoi === 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit;
}

if (
    $userData['role'] !== 'admin' &&
    $tournoiDao->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}

$licence        = $licenceDao->getLicencesParUtilisateur($userData['id'])[0];
$listeDesEquipes = $equipeDao->rechercherEquipesDansTournoi($idTournoi, $_GET['query'] ?? null);

// ─── Paramètres de navigation ─────────────────────────────────────────────────
$categorieEnCours = isset($_GET['categorie'])      ? (int)$_GET['categorie']      : null;
$nbrEquipe        = isset($_GET['NbrEquipeParPoule']) ? (int)$_GET['NbrEquipeParPoule'] : null;
$idPoule          = isset($_GET['id_poule'])        ? (int)$_GET['id_poule']        : null;
$action           = $_GET['action'] ?? null;

$flash   = null;   // message de retour (success | error)
$flashMsg = '';

// ═══════════════════════════════════════════════════════════════════════════════
//  ACTION : créer les poules pour une catégorie
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'creer' && $categorieEnCours && $nbrEquipe) {

    $poulesExistantes = $pouleManager->afficherPoulesPourCategorie($idTournoi, $nbrEquipe, $categorieEnCours);

    $bloque = false;
    foreach ($poulesExistantes as $p) {
        if ($rencontreDao->rencontresCategorieDejaPlanifiees($categorieEnCours, $idTournoi)) {
            $bloque = true;
            break;
        }
    }

    if ($bloque) {
        $flash    = 'error';
        $flashMsg = 'Impossible de manipuler cette poule, des rencontres planifiées existent déjà. '
                  . '<a href="PlacementDesRencontres.php?id_tournoi=' . $idTournoi . '">Déprogrammer</a>.';
    } else {
        $nouvellesPoules = $pouleManager->creerPoulesPourCategorie($idTournoi, $categorieEnCours, $nbrEquipe);
        foreach ($nouvellesPoules as $idp) {
            $rencontreDao->supprimerRencontresParPoule($idp);
            $rencontreDao->createRencontreByPoule($idp, $idTournoi, 1, false);
        }
        $flash    = 'success';
        $flashMsg = 'Poule et rencontres créées avec succès !';
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  ACTION : déplacer une équipe vers une autre poule  (POST)
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'deplacer' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $srcPoule  = (int)$_POST['id_poule'];
    $dstPoule  = (int)$_POST['dstpoule'];
    $idEquipe  = (int)$_POST['equipe'];
    $equipeNom = $_POST['equipeNom'];

    if ($pouleManager->pouleHasRencontreProgrammee($srcPoule, $idTournoi)) {
        $flash    = 'error';
        $flashMsg = 'Impossible de déplacer l\'équipe, des rencontres sont déjà programmées.';
    } else {
        $pouleInfo       = $pouleManager->getPouleById($dstPoule);
        $nouvelleCategorie = $pouleInfo['fk_idcategorie'];

        $rencontreDao->supprimerRencontresParPoule($srcPoule);
        $rencontreDao->supprimerRencontresParPoule($dstPoule);

        $equipeDao->modifierEquipe($idEquipe, $equipeNom, $nouvelleCategorie);
        $equipeDao->modifierEquipeIdPoule($dstPoule, $idEquipe);

        $rencontreDao->createRencontreByPoule($srcPoule, $idTournoi, 1);

        $flash    = 'success';
        $flashMsg = "L'équipe a été déplacée avec succès.";
    }

    // Rester sur la même poule source après déplacement
    $idPoule = $srcPoule;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  ACTION : supprimer une poule vide
// ═══════════════════════════════════════════════════════════════════════════════
if ($action === 'supprimer' && $idPoule) {
    $nbEquipes = $pouleManager->compterEquipesParPoule($idPoule);
    if ($nbEquipes == 0) {
        $pouleManager->deletePoule($idPoule);
        $flash    = 'success';
        $flashMsg = 'Poule supprimée.';
        $idPoule  = null;
    } else {
        $flash    = 'error';
        $flashMsg = 'Impossible de supprimer une poule non vide.';
    }
}

// ─── Construction de la liste des poules (nettoyage poules vides) ─────────────
$toutesLesPoules = [];
foreach ($pouleManager->getAllPoulesByTournoi($idTournoi) as $p) {
    $nbEq = $pouleManager->compterEquipesParPoule($p['id']);
    if ($nbEq == 0) {
        $pouleManager->deletePoule($p['id']);
        continue;
    }
    $toutesLesPoules[] = [
        'idPoule'              => $p['id'],
        'nomPoule'             => $p['nom'],
        'nbrEquipeParPoule'    => $nbEq,
        'rencontresExistesDeja'=> $pouleManager->pouleHasRencontreProgrammee($p['id'], $idTournoi),
    ];
}

// ─── Poule sélectionnée : équipes + flag rencontres ──────────────────────────
$equipesPouleEnCours   = null;
$pouleHasRencontre     = false;
if ($idPoule) {
    $equipesPouleEnCours = $equipeDao->getAllEquipesByPouleId($idPoule);
    $pouleHasRencontre   = $pouleManager->pouleHasRencontreProgrammee($idPoule, $idTournoi);
}

// ─── Liste des poules pour la section "création" ─────────────────────────────
$listeDesPoules = null;
if ($categorieEnCours && $nbrEquipe) {
    $listeDesPoules = $pouleManager->afficherPoulesPourCategorie($idTournoi, $nbrEquipe, $categorieEnCours);
}

// ─── Rendu Twig ──────────────────────────────────────────────────────────────
$template = $twig->load('GestionPoule2.twig');
echo $template->render([
    'email'                => $userData['email'],
    'pageEnCours'          => 'GestionDesPoules',
    'ListeDesTournois'     => $tournoiDao->afficherLesTournois($userData['id']),
    'ListeDesCategorie'    => $equipeDao->getAllCategorieByIdTournoi($idTournoi),
    'idTournoi'            => $idTournoi,
    'categorieEnCours'     => $categorieEnCours,
    'nbrEquipe'            => $nbrEquipe,
    'listeDesPoules'       => $listeDesPoules,
    'toutesLesPoules'      => $toutesLesPoules,
    'idPoule'              => $idPoule,
    'equipesPouleEnCours'  => $equipesPouleEnCours,
    'pouleHasRencontre'    => $pouleHasRencontre,
    'AfficherLesEquipes'   => $listeDesEquipes,
    'licence'              => $licence,
    'flash'                => $flash,
    'flashMsg'             => $flashMsg,
]);
