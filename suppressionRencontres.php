<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';

$tournois = new tournoiDao();
$poulemanager = new PouleManager();

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
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesRencontres',
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
    'ListeDesTournois' => $tournois->afficherLesTournois(),
    'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($_GET['id_tournoi'],true),
    'tournoiEnCours' => $idtournoi,
    'pouleEnCours' => $_GET['idPoule'],
    
  
  
  ]);