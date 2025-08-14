<?php
session_start();
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/clubDao.class.php';
require 'class/terrainDao.class.php';
require 'class/planificationDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('VueTerrain.twig');
$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();
$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$terrainDao = new TerrainDao();
$planification = new planificationDao();

$listeDesTournois = $tournoiDao->afficherLesTournois();
$_SESSION['idTournoi'] = $_GET['id_tournoi'];
//$nombreTerrain = $tournoiDao->getNbTerrainsById($_GET['id_tournoi']);
$nombreTerrain = $terrainDao->compterTerrains($_GET['id_tournoi']);
$terrainSelect=$_GET['terrain'];
$terrainEnCours = $terrainDao->AfficherTerrainParId($_GET['terrain']);





echo $template->render([
    'ListeDesTournois' => $listeDesTournois,
    //'rencontres' => $rencontre->getAllRencontresByTournoiId($_GET['id_tournoi']),
    'rencontres' => $planification->getPlanificationsTerrainAvecDetails($_GET['terrain'],$_GET['id_tournoi']),
    'idTournoi'=> $_SESSION['idTournoi'],
    'nombreTerrain' => $nombreTerrain,
    'terrainSelect' => $terrainSelect,
    'terrainEnCours' => $terrainEnCours


]);
?>