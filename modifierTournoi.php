<?php
require 'security.php';
require 'vendor/autoload.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));


require 'class/clubDao.class.php';
require 'class/arbitreDao.class.php';
require 'class/databaseInformations.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';
require 'class/personneDao.class.php';
require 'class/terrainDao.class.php';
require 'class/PersonneTableDao.class.php';
require 'Lang/lang.php';

$tournoiDao = new tournoiDao();

if (($tournoiDao->droitTournoiClub($_GET['idTournoi'], $userData['id']) == null) and ($_GET['idTournoi'] != "0")) {
   
  exit;
}


$terrain = new TerrainDao();
$personneTable = new PersonneTableDao();

$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();
$listePersonne = new PersonneDao();
$arbitre = new arbitreDao();

if (!isset($_GET['idTournoi']) ){
$idtournoi = 0;
}
else {
    $idtournoi = $_GET['idTournoi'];
  

}

$template = $twig->load('modifierTournoi.twig');


echo $template->render([
 'email' => $userData['email'],
  
  
  'pageEnCours' => 'GestionTournois',
  'infotournoi' => $tournoiDao->getTournoiById($_GET['idTournoi']),
  'tournoiEnCours' => $idtournoi,
  'idTournoi' => $idtournoi,
'ListeDesTournois' => $tournoiDao->afficherLesTournois($userData['id']),
'AfficherClub' => $listeClub->afficherClubs(),
'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),
'AfficherPersonnes' => $listePersonne->recupererToutesLesPersonnes($_GET['idTournoi']),
'AfficherTerrain' => $terrain->AfficherTerrains($_GET['idTournoi']),
'AfficherLesPersonnesCrees' => $personneTable->recupererToutesLesPersonnesParTournoi($_GET['idTournoi']),
'AfficherLesClubsPourArbitres' => $listeClub->clubsParticipatingInTournoi($_GET['idTournoi']),
'AfficherLesArbitres' => $arbitre->afficherArbitres($_GET['idTournoi']),
'tab'=>$_GET['tab'] ?? null,

]);
