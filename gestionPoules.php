<?php
require('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$idCategorie = null;


$tournois = new tournoiDao();
$equipesByCategorie = new EquipeDAO();
$afficheCategorie = new EquipeDAO();
$poules = new PouleManager();
//$poules->creerPoulesPourCategorie($_GET['id_tournoi'],$PouleAuto)




if (isset($_GET['categorie'])) {
  $nbrEquipeEnCours = $_GET['NbrEquipeParPoule'];
  $idCategorie = $_GET['categorie'];
  $categorieEnCours = $_GET['categorie'];
  // $poule = $poules->afficherPoulesPourCategorie($_GET['id_tournoi'],2,$idCategorie);

  if (isset($_GET['NbrEquipeParPoule'])) {


    $poule =  $poules->afficherPoulesPourCategorie($_GET['id_tournoi'], $nbrEquipeEnCours, $idCategorie);

      if (isset($_GET['creation'])) {
        if ($_GET['creation'] == "ok") {
              $poule = $poules->creerPoulesPourCategorie($_GET['id_tournoi'],$idCategorie,$nbrEquipeEnCours);

        }
      }

  }
}



$template = $twig->load('GestionPoules.twig');
echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesPoules',

  'nbrEquipeEnCours' => $nbrEquipeEnCours,

  'nomDuTournoi' => $nomTournoi,

  'ListeDesTournois' => $tournois->afficherLesTournois(),
  'ListeDesCategorie' => $afficheCategorie->getAllCategorieByIdTournoi($_GET['id_tournoi']),
  'idTournoi' => $_GET['id_tournoi'],
  'nombreEquipeParPoule' => $PouleAuto,
  'listeDesPoules' => $poule,
  'categorieEnCours' => $categorieEnCours,
  'nbrEquipe' => $_GET['NbrEquipeParPoule'],


]);
