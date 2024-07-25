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
require 'class/personneDao.class.php';
require 'class/terrainDao.class.php';
require 'class/PersonneTableDao.class.php';
$tournoiDao = new tournoiDao();

$terrain = new TerrainDao();
$personneTable = new PersonneTableDao();

$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();
$listePersonne = new PersonneDao();

if (!isset($_GET['idTournoi']) ){
$idtournoi = 0;
}
else {
    $idtournoi = $_GET['idTournoi'];
  

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
'AfficherPersonnes' => $listePersonne->recupererToutesLesPersonnes($_GET['idTournoi']),
'AfficherTerrain' => $terrain->AfficherTerrains($_GET['idTournoi']),
'AfficherLesPersonnesCrees' => $personneTable->recupererToutesLesPersonnesParTournoi($_GET['idTournoi'])

]);
