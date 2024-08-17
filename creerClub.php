<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('creerClub.twig');

$club = new ClubDAO();
$tournoiDao = new tournoiDao();
$tousLesTournois = $tournoiDao->afficherLesTournois();

$dernierId = null;

foreach ($tousLesTournois as $tournoi) {
    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];
    }
}




echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' =>  'GestionClub',
    'ListeDesClubs' => $club->afficherClubs(),
    'idTournoi' => $dernierId,
  
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);


?>
