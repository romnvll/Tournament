<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'Lang/lang.php';

$tournois = new tournoiDao();
$poulemanager = new PouleManager();
$rencontre = new RencontreDAO();

if (!isset($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
    echo "Aucun tournoi actif en cours.";
    header("Refresh:3; url=ajoutTournoi.php");
    exit();
}

if ($tournois->droitTournoiClub($_GET['id_tournoi'], $userData['id']) == null) {
    exit;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$template = $twig->load('suppressionRencontres.twig');

if (isset($_GET['id_tournoi'])) {
    $idtournoi = $_GET['id_tournoi'];
}

if (isset($_GET['idPoule'])) {
    $idPoule = $_GET['idPoule'];
} else {
    $idPoule = null;
}

if (isset($_GET['idPhaseFinale'])) {
    $idPhaseFinale = $_GET['idPhaseFinale'];
} else {
    $idPhaseFinale = null;
}

// Récupérer les rencontres de phases finales
$rencontresPhasesFinales = $rencontre->getRencontresPhasesFinales($idtournoi);

// Grouper les rencontres par phase finale
$rencontresParPhase = [];

foreach ($rencontresPhasesFinales as $r) {
    $phaseId = $r['phase_finale_id'];
    if (!isset($rencontresParPhase[$phaseId])) {
        $rencontresParPhase[$phaseId] = [
            'libelle' => $r['phase_finale_libelle'] ?? 'Phase non définie',
            'ordre' => $r['phase_finale_ordre'] ?? 0,
            'rencontres' => []
        ];
    }
    $rencontresParPhase[$phaseId]['rencontres'][] = $r;
}

echo $template->render([
    'email' => $userData['email'],
    'pageEnCours' => 'GestionDesRencontres',
    'ListeDesTournois' => $tournois->afficherLesTournois($userData['id']),
    'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($_GET['id_tournoi'], true),
    'idTournoi' => $idtournoi,
    'tournoiEnCours' => $idtournoi,
    'pouleEnCours' => $idPoule,
    'phaseFinaleEnCours' => $idPhaseFinale,
    'rencontresParPhase' => $rencontresParPhase,
]);