<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';
require 'class/labelsDao.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('mesPreferences.twig');

$club = new ClubDAO();
$label = new LabelDao();


$idClub = $club->afficherClubsDetailByMail($_COOKIE['email'])[0]['id'];

$label = $label->getLabelByClubs($idClub);
var_dump($label);
echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' =>  'Users',
 
   
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
