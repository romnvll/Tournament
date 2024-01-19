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
$url1=$_SERVER['REQUEST_URI'];
//header("Refresh: 3; URL=$url1");
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('index.twig');
$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();
$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$listeDesTournois = $tournoiDao->afficherLesTournois();

$listePoules = $poulemanager->getAllPoulesByTournoi($_GET['id_tournoi']);


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

if (isset ($_GET['idPoule'])) {
$idPoule = $_GET['idPoule'];
}
else {
  $idPoule = null;
}

if (isset ($_GET['idPoule'])) {
  $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);

 }



echo $template->render([
    'ListeDesTournois' => $listeDesTournois,
    'afficherLesPoules' => $listePoules ,
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
