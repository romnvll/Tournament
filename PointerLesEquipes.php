<?php
require ('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
session_start();
$_SESSION['idTournoi'] = $_GET['idTournoi'];
$tournoi = new tournoiDao();
$poulemanager = new PouleManager();

$template = $twig->load('PointerLesEquipes.twig');
echo $template->render([
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    //'afficherLesTournois' => $tournoi->getAllTournoi(),
'afficherLesTournois' => $tournoi->afficherLesTournois(),
  'afficherLesPoules' => $poulemanager->getAllPoulesByTournoi($_SESSION['idTournoi']),
  'idTournoi'=> $_SESSION['idTournoi'],
  'listeDesEquipesParPoules' => $poulemanager->getEquipesInPoule($_GET['idPoule']),
  'idPoule' => $_GET['idPoule'],
    
  
  ]);