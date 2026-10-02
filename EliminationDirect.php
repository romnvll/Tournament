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
require 'class/categorie.class.php';
require 'class/arbitreDao.class.php';
require 'class/utilisateurDao.class.php';
require 'Lang/lang.php';


//$_SESSION['id_tournoi'] = $_GET['id_tournoi'];

/*
if (!isset ($_GET['id_tournoi']) || $_GET['id_tournoi'] == 0) {
  echo "Aucun tournoi actif en cours.";
  header("Refresh:3; url=ajoutTournoi.php");
  exit();
}
*/

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(new \Twig\TwigFunction('t', 't'));


$poulemanager = new PouleManager();
$rencontre = new RencontreDAO();
$equipeDao = new EquipeDAO();
$tournois = new tournoiDao();
$terrain = new TerrainDao();
$creneaux = new creneauxDao();
$planification = new planificationDao();
$arbitre = new arbitreDao();
$categorie = new CategorieDao();


$idTournoi = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;

if (
   $userData['role'] !== 'admin' &&
    $tournois->droitTournoiClub($idTournoi, $userData['id']) === null
) {
    exit;
}


    $Poules = $categorie->afficherClassementParCategorie($_GET['id_tournoi']);
$infoArbre = null; 
if (isset($_GET['idCategorie'])) {
    $rencontreDao = new RencontreDAO();
    $infoArbre = $rencontreDao->getInfoQualifiesParPoule($_GET['id_tournoi'],$_GET['idCategorie']);
    
    $rencontreDao->afficherArbreTournoi($_GET['id_tournoi'],$_GET['idCategorie']);
    $planification->convertLabelsToRencontres($_GET['id_tournoi'],$_GET['idCategorie']);
    
}
$template = $twig->load('eliminationDirect.twig');
echo $template->render([
  'nbrTour'=> $infoArbre,
  'email' => $userData['email'],
  'pageEnCours' => 'GestionDesRencontres',

  //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
  
  'idTournoi'=> $_GET['id_tournoi'],
 'classement' => $Poules,
 
 

 
]);
