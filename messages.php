<?php

/* COOKIE des messages */
function getVisiteurId(): string {
    if (!empty($_COOKIE['visiteur_id']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['visiteur_id'])) {
        return $_COOKIE['visiteur_id'];
    }
    $id = bin2hex(random_bytes(16));
    setcookie('visiteur_id', $id, [
        'expires'  => time() + 60 * 60 * 24 * 365 * 2, // 2 ans
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['visiteur_id'] = $id; // dispo immédiatement pour ce même chargement
    return $id;
}






require 'vendor/autoload.php';
require 'class/messageDao.class.php';
require 'class/equipeDao.class.php';
require 'class/tournoiDao.class.php';
require 'Lang/lang.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('messages.twig');

if (!isset($_GET['id_equipe']) || (int)$_GET['id_equipe'] === 0) {
    echo "Aucune équipe sélectionnée.";
    exit();
}

$idEquipe   = (int)$_GET['id_equipe'];
$idTournoi  = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
$idClub     = isset($_GET['id_club']) ? (int)$_GET['id_club'] : null;
$idCategorie = isset($_GET['idCategorie']) ? (int)$_GET['idCategorie'] : null;

$equipeDao  = new equipeDao();
$messageDao = new MessageDAO();
$tournoiDao = new tournoiDao();

$equipeInfo = $equipeDao->getEquipeById($idEquipe);

// Marquer "lu" un message précis si demandé explicitement (ouverture du message)
if (isset($_GET['marquer_lu'])) {
    $messageDao->marquerLu((int)$_GET['marquer_lu'], $idEquipe,getVisiteurId());
}

$messagesEquipe = $messageDao->afficherMessagesParEquipe($idEquipe,getVisiteurId());

// Cette page dédiée constitue une consultation explicite : tous les messages
// listés ici sont donc marqués "lus" (et pas seulement "reçus" comme le ferait
// la simple présence de la cloche sur index.php).
foreach ($messagesEquipe as $msg) {
    $messageDao->marquerLu((int)$msg['id'], $idEquipe, getVisiteurId());
}

// On recharge la liste pour que l'affichage reflète immédiatement le statut "lu"
// (sinon le badge "non lu" resterait affiché sur ce premier chargement).
$messagesEquipe = $messageDao->afficherMessagesParEquipe($idEquipe, getVisiteurId());

echo $template->render([
    'idTournoi'    => $idTournoi,
    'idEquipe'     => $idEquipe,
    'idClub'       => $idClub,
    'idCategorie'  => $idCategorie,
    'equipeInfo'   => $equipeInfo,
    'tournoiInfo'  => $idTournoi ? $tournoiDao->getTournoiById($idTournoi) : null,
    'messagesEquipe' => $messagesEquipe,
]);
?>
