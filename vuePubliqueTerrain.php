<?php
session_start();
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/clubDao.class.php';
require 'class/planificationDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('VuePubliqueTerrain.twig');
$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();

$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$listeDesTournois = $tournoiDao->afficherLesTournois();
$_SESSION['idTournoi'] = $_GET['id_tournoi'];

$affichagePlanification = new planificationDao();
$affichagePlanification = $affichagePlanification->getPlanificationsTerrainAvecDetails($_GET['terrain'],$_GET['id_tournoi']);




echo $template->render([
    'ListeDesTournois' => $listeDesTournois,

    'idTournoi'=> $_SESSION['idTournoi'],
    'rencontres' => $affichagePlanification
    


]);


?>