<?php
require ('security.php');
require 'class/equipeDao.class.php';
//require 'class/equipe.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';
//require 'class/tournoi.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/terrainDao.class.php';
require 'vendor/autoload.php';
require 'class/creneauxDao.class.php';
require 'class/planificationDao.class.php';
require 'class/arbitreDao.class.php';

session_start();
$_SESSION['idTournoi'] = $_GET['idTournoi'];


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


$poulemanager = new PouleManager();
$rencontre = new RencontreDAO();
$equipeDao = new EquipeDAO();
$tournois = new tournoiDao();
$terrain = new TerrainDao();
$creneaux = new creneauxDao();
$planification = new planificationDao();
$arbitre = new arbitreDao();


//



$template = $twig->load('gestionToutesLesRencontres.twig');

if (!isset($_GET['id_tournoi'])) {
  $listedestournois = $tournois->afficherLesTournois($_GET['id_tournoi']);
  $nbrterrain = null;
  $table = null;
  $listdecreneau = null;
  $planification = null;
  $planificationSansCreneauNiTerrain = null;
  $libelleParTournoi = null;
  $listeDesArbitres = null;
  $tournoiInfo = null;
  $lastCreneau=null;
  
} else {
 $listedestournois = $tournois->afficherLesTournois($_GET['id_tournoi']);
 $nbrterrain = $terrain->AfficherTerrains($_GET['id_tournoi']);
 $listdecreneaux = $creneaux->afficherCreneaux($_GET['id_tournoi']);
 $ToutesPlanification = $planification->afficherPlanifications($_GET['id_tournoi']);
  $planificationSansCreneauNiTerrain = $planification->afficherRencontresSansPlanification($_GET['id_tournoi']);
  $libelleParTournoi = $planification->listerLabelsParTournoi($_GET['id_tournoi']);
  $listeDesArbitres = $arbitre->afficherArbitres($_GET['id_tournoi']);
  $tournoiInfo = $tournois->getTournoiById($_GET['id_tournoi']);
 
  
}





echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesRencontres',

  //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
  
  'idTournoi'=> $_GET['id_tournoi'],

 

  'afficherPlanification' =>  $ToutesPlanification,
    'planificationSansCreneauNiTerrain' => $planificationSansCreneauNiTerrain,

    'ListeDesTournois' => $listedestournois,
    'terrains' => $nbrterrain,
    'listCreneaux' => $listdecreneaux,
    'libelleParTournoi' => $libelleParTournoi,
    'listeDesArbitres' => $listeDesArbitres,
    'tournoiInfo'   => $tournoiInfo,
   

  

 
 


]);


?>