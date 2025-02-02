<?php
session_start();
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/equipeDao.class.php';
require 'class/clubDao.class.php';
require 'class/planificationDao.class.php';
require 'class/labelsDao.class.php';

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
$RencontreByPoule=null;
$Labels= new LabelDao();
$listeDesRencontresByTerrain = null;

<<<<<<< HEAD
=======

if (isset ($_GET['affichageByClubs'])) {
  $affichageByClubs = true;
}

if (isset ($_GET['affichageByTeam'])) {
  $affichageByTeam = true;
  $listeDesRencontresByTeam = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
}

<<<<<<< HEAD
<<<<<<< HEAD
>>>>>>> f42c26c (amelioration vue public)
=======
=======
>>>>>>> 0566b18 (fix : harmonisation de la vue terrain)
if (isset ($_GET['affichageByTerrain'])) {
  $affichageByTerrain = true;
  $listeDesRencontresByTerrain = $rencontre->afficherRencontresParTerrainEtTournoi($_GET['terrain'],$_GET['id_tournoi']);
}

<<<<<<< HEAD
>>>>>>> 0566b18 (fix : harmonisation de la vue terrain)
=======
>>>>>>> 0566b18 (fix : harmonisation de la vue terrain)
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

//derniere poules des équipes :

$equipesAvecPoule = [];

foreach ($listeDesEquipesByClubs as $equipe) {
    $equipesAvecPoule[] = [
        'id' => $equipe['id'],
        'nom' => $equipe['nom'],
        'Nom_categorie' => $equipe['Nom_categorie'],
        'idPoule' => $poulemanager->getDernierePouleIdParEquipe($equipe['id'])
    ];
}


//




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
  $Labels = $Labels->getLabelsWithCreneauxByTournoiId($_GET['id_tournoi']);

}

else {
  $listeClubsParticipants=null;
  $Labels = $Labels->getLabelsWithCreneauxByTournoiId(0);
}

if (isset ($_GET['idPoule'])) {
$idPoule = $_GET['idPoule'];
}
else {
  $idPoule = null;
}

if (isset ($_GET['idPoule'])) {
  //$GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule']);
  
 
  if ($poulemanager->getPouleById($_GET['idPoule'])['is_classement'] == 1 ) {
    
   $RencontreByPoule = $rencontre->getRencontreByPoule($idPoule,$_GET['id_tournoi'],1);
   $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule'],1);

  }
   else {
   $RencontreByPoule = $rencontre->getRencontreByPoule($idPoule,$_GET['id_tournoi'],0);
   $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_GET['idPoule'],0);

   }




   }

 else {
  $GetResultatDesPoules = null;
 }



echo $template->render([
    'infoTournoiEnCours'=> $tournoiDao->getTournoiById($_GET['id_tournoi']),
    'ListeDesTournois' => $listeDesTournois,
    'afficherLesPoules' => $listePoulesParEquipe ,
    'idTournoi'=> $_SESSION['idTournoi'],
    'RencontreByPoule' => $RencontreByPoule,
    'IdPoules' => $idPoule,
    'IdClub' => $idclub,
<<<<<<< HEAD
=======
    'affichageByClubs'=> $affichageByClubs,
    'affichageByTeam' =>$affichageByTeam,
>>>>>>> f42c26c (amelioration vue public)
    'listeDesCLubs' => $listeClubsParticipants,
    'listeDesRencontreByClubs' => $listeDesRencontreByClubs,
    'listeDesEquipesByClubs' =>$listeDesEquipesByClubs,
    'listeDesRencontresByTerrain' => $listeDesRencontresByTerrain,
    'listeDesEquipesByEquipeId' => $listeDesRenbcontreByEquipe,
    'IdEquipe' => $idequipe,
    'listeDesRencontreByTeam' =>$listeDesRencontresByTeam,
    'affichageByTerrain' => $listeDesRencontresByTerrain,
    'resultatRencontres'=> $GetResultatDesPoules,
    'getNomClubCourant' => $clubdao->getClubById($_GET['id_club'])['nom'],
    'getNomEquipeCourant' => $equipeDao->getEquipeById($_GET['id_equipe'])['nom'],
    'labels' => $Labels,
    'equipesAvecPoule' => $equipesAvecPoule
  
    
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poulesDao->getAllPoulesByTournoi($_GET['idTournoi']),

]);
?>
