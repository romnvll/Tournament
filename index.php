<?php
session_start();
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/clubDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('index.twig');
$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();
$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$listeDesTournois = $tournoiDao->afficherLesTournois();

if (isset ($_GET['id_equipe'])) {
$listePoulesParEquipe = $poulemanager->getPoulesByEquipeId($_GET['id_equipe']);
}

else {

  $listePoulesParEquipe = null;
}


if (isset ($_GET['id_club'])) {
  $idclub = $_GET['id_club'];

  $listeDesRencontreByClubs = $rencontre->afficherRencontreByTournoiByClub($_GET['id_tournoi'],$_GET['id_club']);
  $listeDesEquipesByClubs = $equipeDao->getAllEquipeByIdTournoiAndClub($_GET['id_tournoi'],$_GET['id_club']);
}

else {
  $listeDesRencontreByClubs = null;
  $listeDesEquipesByClubs = null;
  $idclub = null;
}

if (isset ($_GET['id_equipe'])) {

  $listeDesRenbcontreByEquipe = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
$idequipe = $_GET['id_equipe'];
}



else {
  $idequipe = null;
  $listeDesRenbcontreByEquipe = null;
}

if (isset ($_GET['id_tournoi'])) {
  $_SESSION['idTournoi'] = $_GET['id_tournoi'];
  $listeClubsParticipants = $clubdao->clubsParticipatingInTournoi($_GET['id_tournoi']);

}

else {
  $listeClubsParticipants=null;
}

if (isset ($_GET['idPoule'])) {
$idPoule = $_GET['idPoule'];
}
else {
  $idPoule = null;
}

if (isset ($_GET['idPoule'])) {
  $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);

 }

 else {
  $GetResultatDesPoules = null;
 }



echo $template->render([
    'ListeDesTournois' => $listeDesTournois,
    'afficherLesPoules' => $listePoulesParEquipe ,
    'idTournoi'=> $_SESSION['idTournoi'],
    'RencontreByPoule' => $rencontre->getRencontreByPoule($idPoule),
    'IdPoules' => $idPoule,
    'IdClub' => $idclub,
    'listeDesCLubs' => $listeClubsParticipants,
    'listeDesRencontreByClubs' => $listeDesRencontreByClubs,
    'listeDesEquipesByClubs' =>$listeDesEquipesByClubs,
    'listeDesEquipesByEquipeId' => $listeDesRenbcontreByEquipe,
    'IdEquipe' => $idequipe,
    'resultatRencontres'=> $GetResultatDesPoules
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poulesDao->getAllPoulesByTournoi($_GET['idTournoi']),

]);
?>
