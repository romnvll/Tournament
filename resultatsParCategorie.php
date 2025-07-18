<?php


require ('security.php');
require 'class/equipeDao.class.php';
require 'class/tournoiDao.class.php';
require 'vendor/autoload.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$tournoi = new tournoiDao();




$template = $twig->load('resultatsParCategorie.twig');
echo $template->render([
  'email' => $userData['email'],
  'logo' => $userData['logo'],
  'pageEnCours' => 'GestionDesRencontres',
  'afficherLesTournois' => $tournoi->afficherLesTournois($userData['id']),

  'idTournoi'=> $_GET['id_tournoi'],
  'tournoiEnCours' => $_GET['id_tournoi'],
  'classementParCategorie' => $tournoi->getClassementParCategorie($_GET['id_tournoi']),

]);


?>