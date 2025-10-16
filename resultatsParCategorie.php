<?php


require ('security.php');
require 'class/equipeDao.class.php';
require 'class/tournoiDao.class.php';
require 'vendor/autoload.php';
require 'Lang/lang.php';


$idTournoi = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;

if (
    $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}





$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$tournoi = new tournoiDao();




$template = $twig->load('resultatsParCategorie.twig');
echo $template->render([
  'email' => $userData['email'],
  
  'pageEnCours' => 'GestionDesRencontres',
  'afficherLesTournois' => $tournoi->afficherLesTournois($userData['id']),

  'idTournoi'=> $_GET['id_tournoi'],
  'tournoiEnCours' => $_GET['id_tournoi'],
  'classementParCategorie' => $tournoi->getClassementParCategorie($_GET['id_tournoi']),

]);


?>