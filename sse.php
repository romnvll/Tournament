<?php

require 'vendor/autoload.php';
require 'class/rencontreDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/messageDao.class.php';
require 'class/creneauxDao.class.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

/* Paramètres */

$idTournoi   = (int) ($_GET['id_tournoi'] ?? 0);
$idPoule     = isset($_GET['idPoule'])     ? (int) $_GET['idPoule']     : null;
$idEquipe    = isset($_GET['id_equipe'])   ? (int) $_GET['id_equipe']   : null;
$idCategorie = isset($_GET['idCategorie']) ? (int) $_GET['idCategorie'] : null;

if (!$idTournoi) {
    http_response_code(400);
    exit;
}

/* Cookie visiteur (même validation que getVisiteurId) */

$visiteurId = $_COOKIE['visiteur_id'] ?? null;
if ($visiteurId !== null && !preg_match('/^[a-f0-9]{32}$/', $visiteurId)) {
    $visiteurId = null;
}

/* Envoi d'un événement */

function sendEvent(string $event, $data): void
{
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    if (ob_get_level() > 0) {
        @ob_flush();
    }
    flush();
}

/* Hash reçus du navigateur lors de la reconnexion */

$hashes = [
    'score' => null, 'ranking' => null, 'finale' => null,
    'bell' => null, 'creneau' => null, 'classement_final' => null,
];

$prev = json_decode(base64_decode($_SERVER['HTTP_LAST_EVENT_ID'] ?? ''), true);
if (is_array($prev)) {
    $hashes = array_merge($hashes, array_intersect_key($prev, $hashes));
}

echo "retry: 20000\n\n";
flush();

/* DAO (connexions ouvertes seulement pour cette passe) */

$rencontreDao = new RencontreDAO();
$poulemanager = new PouleManager();
$messageDao   = new MessageDAO();
$creneauxDao  = new creneauxDao();

/* Rencontres */

if ($idPoule) {
    $poule = $poulemanager->getPouleById($idPoule);

    if ($poule && isset($poule['is_classement']) && $poule['is_classement'] == 3) {
        $rencontres = $rencontreDao->getRencontreByPoule($idPoule, 3, 'index', false);
    } else {
        $rencontres = $rencontreDao->getRencontreByPoule($idPoule, 1, 'index', true);
    }
} else {
    $rencontres = $rencontreDao->afficherRencontreByTournoiByEquipe($idTournoi, $idEquipe);
}

$hash = md5(json_encode($rencontres));
if ($hash !== $hashes['score']) {
    $hashes['score'] = $hash;
    sendEvent('score_update', $rencontres);
}

/* Phase finale */

if ($idCategorie) {
    $rencontresFinale = $rencontreDao->getRencontreByCategorie($idCategorie, $idTournoi, 3, 'index');

    $hash = md5(json_encode($rencontresFinale));
    if ($hash !== $hashes['finale']) {
        $hashes['finale'] = $hash;
        sendEvent('finale_update', $rencontresFinale);
    }
}

/* Classement de poule */

if ($idPoule) {
    $classement = $rencontreDao->GetResultatDesPoules($idPoule, 1);

    $hash = md5(json_encode($classement));
    if ($hash !== $hashes['ranking']) {
        $hashes['ranking'] = $hash;
        sendEvent('ranking_update', $classement);
    }
}

/* Classement final */

if ($idCategorie) {
    $classement = $poulemanager->getClassementFinal($idTournoi, $idCategorie);

    $hash = md5(json_encode($classement));
    if ($hash !== $hashes['classement_final']) {
        $hashes['classement_final'] = $hash;
        sendEvent('classement_final_update', $classement);
    }
}

/* Messages non lus (cloche) */

if ($idEquipe) {
    $nbNonLus = $visiteurId
        ? $messageDao->compterMessagesNonLusParEquipe($idEquipe, $visiteurId, $idTournoi)
        : 0;

    $hash = md5((string) $nbNonLus);
    if ($hash !== $hashes['bell']) {
        $hashes['bell'] = $hash;
        sendEvent('bell_update', ['nbMessagesNonLus' => $nbNonLus]);
    }
}

/* Créneau en cours / suivant */

$timers = $creneauxDao->getCreneauEnCoursEtSuivant($idTournoi);

$hash = md5(json_encode($timers));
if ($hash !== $hashes['creneau']) {
    $hashes['creneau'] = $hash;
    sendEvent('creneau_update', $timers);
}

/* On mémorise l'état côté navigateur pour la prochaine reconnexion */

echo "event: sync\n";
echo 'id: ' . base64_encode(json_encode($hashes)) . "\n";
echo "data: 1\n\n";
flush();