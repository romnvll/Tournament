<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';

$tournois = new tournoiDao();
$poulemanager = new PouleManager();



if (!isset ($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
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
$template = $twig->load('suppressionRencontres.twig');

if (isset ($_GET['id_tournoi'])) {
    $idtournoi = $_GET['id_tournoi'];
  
}

if (isset ($_GET['idPoule'])) {
    $idPoule = $_GET['idPoule'];
}

echo $template->render([
  'email' => $userData['email'],
  'logo' => $userData['logo'],
    'pageEnCours' => 'GestionDesRencontres',
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
    'ListeDesTournois' => $tournois->afficherLesTournois($userData['id']),
    'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($_GET['id_tournoi'],true),
    'idTournoi' => $idtournoi,
    'tournoiEnCours' => $idtournoi,
    'pouleEnCours' => $_GET['idPoule'],
    
  
  
  ]);