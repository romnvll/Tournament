<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('modifierClub.twig');

$club = new ClubDAO();



echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' =>  'GestionClub',
    'ListeDesClubs' => $club->afficherClubs(),
    'isModify' => true,
    'afficheclub' => $club->getClubById($_GET['idclub']),
    'idClub' => $_GET['idclub']
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
