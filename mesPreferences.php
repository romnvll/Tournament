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





echo $template->render([
  'email' => $userData['email'],
  'logo' => $userData['logo'],
  'pageEnCours' =>  'Users',
 
   
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
