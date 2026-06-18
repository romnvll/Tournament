<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/messageDao.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, ['cache' => false, 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('gestionMessages.twig');

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

$messageDao = new messageDao();

$tournoiInfo = $tournois->getTournoiById($idTournoi);
$categories  = $messageDao->listerCategoriesDuTournoi($idTournoi);
$poules      = $messageDao->listerPoulesDuTournoi($idTournoi);
$equipes     = $messageDao->listerEquipesDuTournoi($idTournoi);

$messages = [];
$erreurs  = [];

// ── POST : création d'un message ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'envoyer_message') {
    $contenu     = trim($_POST['contenu'] ?? '');
    $categorieId = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : null;
    $pouleId     = !empty($_POST['poule_id']) ? (int)$_POST['poule_id'] : null;
    $equipeId    = !empty($_POST['equipe_id']) ? (int)$_POST['equipe_id'] : null;

    if ($contenu === '') {
        $erreurs[] = "Le message ne peut pas être vide.";
    } else {
        // Priorité au ciblage le plus précis si plusieurs valeurs sont présentes
        // (équipe > poule > catégorie), conformément à la cascade du formulaire.
        if ($equipeId !== null) {
            $categorieId = null;
            $pouleId     = null;
        } elseif ($pouleId !== null) {
            $categorieId = null;
        }

        $resultat = $messageDao->creerMessage($idTournoi, $contenu, $categorieId, $pouleId, $equipeId, $userData['id']);

        if ($resultat) {
            $messages[] = "Message envoyé avec succès !";
        } else {
            $erreurs[] = "Une erreur est survenue lors de l'envoi du message.";
        }
    }
}

// ── POST : suppression d'un message ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_message') {
    $messageId = (int)($_POST['message_id'] ?? 0);
    if ($messageId > 0) {
        $messageDao->supprimerMessage($messageId);
        $messages[] = "Message supprimé.";
    }
}

// Recharger l'historique après chaque action
$historiqueMessages = $messageDao->afficherHistoriqueMessages($idTournoi);

// Si on consulte le détail d'un message précis (qui a vu / lu)
$detailMessageId   = isset($_GET['detail_message']) ? (int)$_GET['detail_message'] : null;
$detailDestinataires = $detailMessageId ? $messageDao->afficherStatutParEquipePourMessage($detailMessageId) : null;

// ── Rendu Twig ───────────────────────────────────────────────────────────────
echo $template->render([
    'email'               => $userData['email'],
    'pageEnCours'         => 'GestionDesMessages',
    'idTournoi'           => $idTournoi,
    'tournoiInfo'         => $tournoiInfo,
    'categories'          => $categories,
    'poules'              => $poules,
    'equipes'             => $equipes,
    'historiqueMessages'  => $historiqueMessages,
    'detailMessageId'     => $detailMessageId,
    'detailDestinataires' => $detailDestinataires,
    'messages'            => $messages,
    'erreurs'             => $erreurs,
    'ListeDesTournois'    => $tournois->afficherLesTournois($userData['id']),
]);
?>
