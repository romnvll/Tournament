<?php
require 'security.php';
require 'vendor/autoload.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


require 'class/clubDao.class.php';
require 'class/databaseInformations.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';
$tournoiDao = new tournoiDao();
//$tournoiDao->getAllTournoi();

$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();


if (!isset($_GET['idTournoi']) ){
$idtournoi = 0;
}
else {
    $idtournoi = $_GET['idTournoi'];
    session_start();
    $_SESSION['tournoiDefaut'] = $_GET['idTournoi'];

}

$template = $twig->load('modifierTournoi.twig');


echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionTournois',
  'infotournoi' => $tournoiDao->getTournoiById($_GET['idTournoi']),
  'tournoiEnCours' => $idtournoi,
'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
'AfficherClub' => $listeClub->afficherClubs(),
'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
