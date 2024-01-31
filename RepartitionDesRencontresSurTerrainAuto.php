<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('RepartitionDesRencontresSurTerrainAuto.twig');



$tournois = new tournoiDao();

$nbrterrain = $tournois->getNbTerrainsById($_GET['id_tournoi']);

echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesRencontres',
  'nbrTerrain' => $nbrterrain,
 
  'ListeDesTournois' => $tournois->afficherLesTournois(),
  'idTournoi' => $_GET['id_tournoi'],
  'categorie' => $tournois->getCategoriesPourTournoi($_GET['id_tournoi']),


  'email' => $_COOKIE['email'],

  


]);
