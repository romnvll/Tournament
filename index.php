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
require 'class/terrainDao.class.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);

$twig->addFilter(new \Twig\TwigFilter('shuffle', function ($array) {
    shuffle($array);
    return $array;
}));
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('index.twig');





$rencontre = new RencontreDAO();
$tournoiDao = new tournoiDao();
$poulemanager = new PouleManager();
$clubdao = new ClubDAO();
$equipeDao = new EquipeDAO();
$listeDesTournois = $tournoiDao->afficherTousLesTournois();
$RencontreByPoule=null;
$Labels= new LabelDao();
$listeDesRencontresByTerrain = null;
$terrain = new TerrainDao();

if (isset ($_GET['id_tournoi'])) {
  $nbrterrain = $terrain->compterTerrains($_GET['id_tournoi']);
}


if (isset ($_GET['affichageByClubs'])) {
  $affichageByClubs = true;
}
else {
  $affichageByClubs = null;
}

if (isset ($_GET['affichageByTeam'])) {
  
  $affichageByTeam = true;
  $listeDesRencontresByTeam = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
}
else {
  $affichageByTeam=null;
  $listeDesRencontresByTeam = null;
}

if (isset ($_GET['affichageByTerrain'])) {
  $affichageByTerrain = true;
  $listeDesRencontresByTerrain = $rencontre->afficherRencontresParTerrainEtTournoi($_GET['terrain'],$_GET['id_tournoi']);
}

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
  $nomClub = $clubdao->getClubById($_GET['id_club'])['nom'];
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
  $nomClub = null;
  $equipesAvecPoule = null;
  $listeDesRencontreByClubs = null;
  $listeDesEquipesByClubs = null;
  $idclub = null;
}

if (isset ($_GET['id_equipe'])) {

  $listeDesRenbcontreByEquipe = $rencontre->afficherRencontreByTournoiByEquipe($_GET['id_tournoi'],$_GET['id_equipe']);
$idequipe = $_GET['id_equipe'];


$equipeNom = $equipeDao->getEquipeById($_GET['id_equipe'])['nom'];
}



else {
  $equipeNom = null;
  $idequipe = null;
  $listeDesRenbcontreByEquipe = null;
}

if (isset ($_GET['id_tournoi'])) {
  $_SESSION['idTournoi'] = $_GET['id_tournoi'];
  $idTournoi= $_GET['id_tournoi'];
  $listeClubsParticipants = $clubdao->clubsParticipatingInTournoi($_GET['id_tournoi']);
  $Labels = $Labels->getLabelsWithCreneauxByTournoiId($_GET['id_tournoi']);

}

else {
  $listeClubsParticipants=null;
  $Labels = $Labels->getLabelsWithCreneauxByTournoiId(0);
  $idTournoi=0;
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



 
//gestion des sponsor

if ( $tournoiDao->getTournoiById($idTournoi)['gestionPartenaires'] == 1) {
  
  //recuperation des partenaires du club qui a organiser ce tournoi
  require_once 'class/SponsorDAO.class.php';
  $sponsorDao = new SponsorDAO();
  $listeDesPartenaires = $sponsorDao->getSponsorsParClub($tournoiDao->getTournoiById($idTournoi)['club_id']);
 
}
else {
  $listeDesPartenaires = null;
}


echo $template->render([
    'infoTournoiEnCours'=> $tournoiDao->getTournoiById($idTournoi),
    'ListeDesTournois' => $listeDesTournois,
    'afficherLesPoules' => $listePoulesParEquipe ,
    'idTournoi'=> $_SESSION['idTournoi'],
    'RencontreByPoule' => $RencontreByPoule,
    'IdPoules' => $idPoule,
    'IdClub' => $idclub,
    'affichageByClubs'=> $affichageByClubs,
    'affichageByTeam' =>$affichageByTeam,
    'listeDesCLubs' => $listeClubsParticipants,
    'listeDesRencontreByClubs' => $listeDesRencontreByClubs,
    'listeDesEquipesByClubs' =>$listeDesEquipesByClubs,
    'listeDesRencontresByTerrain' => $listeDesRencontresByTerrain,
    'listeDesEquipesByEquipeId' => $listeDesRenbcontreByEquipe,
    'IdEquipe' => $idequipe,
    'listeDesRencontreByTeam' =>$listeDesRencontresByTeam,
    'affichageByTerrain' => $listeDesRencontresByTerrain,
    'resultatRencontres'=> $GetResultatDesPoules,
    'getNomClubCourant' => $nomClub,
    'getNomEquipeCourant' => $equipeNom,
    'labels' => $Labels,
    'equipesAvecPoule' => $equipesAvecPoule,
    'nbrTerrains' => $nbrterrain,
    'partenaires' => $listeDesPartenaires,
  
    
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poulesDao->getAllPoulesByTournoi($_GET['idTournoi']),

]);
?>
