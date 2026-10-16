<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/creneauxDao.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('matchAmical.twig');

// ── Vérification du tournoi ──────────────────────────────────────────────────
if (!isset($_GET['id_tournoi']) || (int)$_GET['id_tournoi'] === 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}

$idTournoi = (int)$_GET['id_tournoi'];
$tournois  = new tournoiDao();

if (
    $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}

$equipeDao     = new equipeDao();
$rencontresDao = new rencontreDao();
$creneauxDao   = new creneauxDao();
$tournoiInfo   = $tournois->getTournoiById($idTournoi);
$equipes       = $equipeDao->getAllEquipeByIdTournoi($idTournoi);

// Type de rencontre amical = 4
$typeRencontreAmical = 4;

$messages = [];
$erreurs  = [];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? '';

// ── POST : création d'un match amical ────────────────────────────────────────
if ($method === 'POST' && $action === 'creer_match_amical') {
    $equipe1Id     = (int)($_POST['equipe1_id'] ?? 0);
    $equipe2Id     = (int)($_POST['equipe2_id'] ?? 0);
    $creneauChoisi = $_POST['creneau_choisi'] ?? ''; // format "terrain_id|creneau_id"

    // Les équipes doivent appartenir au tournoi
    $idsEquipes = array_map('intval', array_column($equipes, 'id'));

    if ($equipe1Id === 0 || $equipe2Id === 0) {
        $erreurs[] = "Veuillez sélectionner deux équipes.";
    } elseif ($equipe1Id === $equipe2Id) {
        $erreurs[] = "Les deux équipes doivent être différentes.";
    } elseif (!in_array($equipe1Id, $idsEquipes, true) || !in_array($equipe2Id, $idsEquipes, true)) {
        $erreurs[] = "Équipe invalide pour ce tournoi.";
    } else {
        $rencontreId = $rencontresDao->insertRencontre(
            $equipe1Id,
            $equipe2Id,
            $idTournoi,
            $typeRencontreAmical
        );

        if ($rencontreId) {
            $messages[] = "Match amical créé avec succès !";

            if ($creneauChoisi !== '' && strpos($creneauChoisi, '|') !== false) {
                [$terrainId, $creneauId] = array_map('intval', explode('|', $creneauChoisi, 2));

                $place = $rencontresDao->placerRencontreSurCreneau(
                    $rencontreId, $equipe1Id, $equipe2Id, $terrainId, $creneauId, $idTournoi
                );

                if ($place) {
                    $messages[] = "Match programmé sur le créneau sélectionné.";
                } else {
                    $erreurs[] = "Une des deux équipes joue déjà sur ce créneau (ou le créneau vient d'être occupé) — le match reste à planifier manuellement.";
                }
            }
        } else {
            $erreurs[] = "Erreur lors de la création du match amical.";
        }
    }
}

// ── POST : supprimer un match amical ─────────────────────────────────────────
if ($method === 'POST' && $action === 'supprimer_match') {
    $rencontreId = (int)($_POST['rencontre_id'] ?? 0);
    if ($rencontreId > 0) {
        // TODO sécurité : vérifier dans le DAO que cette rencontre appartient bien à $idTournoi
        $rencontresDao->supprimerRencontre($rencontreId);
        $messages[] = "Match amical supprimé.";
    }
}

// Recharger les rencontres amicales après chaque action
$rencontresAmicales = $rencontresDao->afficherRencontresParType($idTournoi, $typeRencontreAmical);

// ── Créneaux libres à partir de maintenant ───────────────────────────────────
$creneauxLibres = $creneauxDao->getCreneauxLibres($idTournoi, date('H:i:s'), 12);

// ── Rendu Twig ───────────────────────────────────────────────────────────────
echo $template->render([
    'email'             => $userData['email'],
    'pageEnCours'       => 'GestionDesRencontres',
    'idTournoi'         => $idTournoi,
    'tournoiInfo'       => $tournoiInfo,
    'equipes'           => $equipes,
    'rencontresAmicales'=> $rencontresAmicales,
    'messages'          => $messages,
    'erreurs'           => $erreurs,
    'creneauxLibres'    => $creneauxLibres,
    'ListeDesTournois'  => $tournois->afficherLesTournois($userData['id']),
]);