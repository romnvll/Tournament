<?php
require('security.php');
require 'vendor/autoload.php';
require 'class/pouleManagerDao.class.php';
require 'class/tournoiDao.class.php';
require 'class/equipeDao.class.php';
require 'class/rencontreDao.class.php';
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





if (isset($_GET['categorie'])) {
  $nbrEquipeEnCours = $_GET['NbrEquipeParPoule'];
  $idCategorie = $_GET['categorie'];
  $categorieEnCours = $_GET['categorie'];

  if (isset($_GET['NbrEquipeParPoule'])) {

    $rencontre = new RencontreDAO();
    $poule =  $poules->afficherPoulesPourCategorie($_GET['id_tournoi'], $nbrEquipeEnCours, $idCategorie);
   
   
    
      if (isset($_GET['creation'])) {
       


        if ($_GET['creation'] == "ok") {
              $rencontreDejaPlanifiee = $rencontre->rencontresCategorieDejaPlanifiees($idCategorie,$_GET['id_tournoi']);
           
          if ( $rencontreDejaPlanifiee) {
            echo "<div class=\" alert alert-danger alert-dismissible fade show d-flex \" role=\"alert\" style=\"z-index: 1050;\">
            <button type=\"button\" class=\"btn-close\" data-dismiss=\"alert\" aria-label=\"Close\"></button>
            <div>
              Impossible de manipuler cette poule, des rencontres planifiées existent déja.
            </div>
          </div>";
          exit(1);
          }

              $poule = $poules->creerPoulesPourCategorie($_GET['id_tournoi'],$idCategorie,$nbrEquipeEnCours);
              
              
              // Rediriger avec un paramètre supplémentaire pour afficher la modale
        //header("Location: gestionPoules.php?id_tournoi=" . $_GET['id_tournoi'] . "&categorie=" . $categorieEnCours . "&NbrEquipeParPoule=" . $_GET['NbrEquipeParPoule'] . "&modal=success");
        
        echo "<div class=\" alert alert-success alert-dismissible fade show d-flex \" role=\"alert\" style=\"z-index: 1050;\">
        <button type=\"button\" class=\"btn-close\" data-dismiss=\"alert\" aria-label=\"Close\"></button>
        <div>
          La poule est créé :)
        </div>
      </div>";



        exit();

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
  'modal' => isset($_GET['modal']) ? $_GET['modal'] : null, // Passez la variable de modal à Twig


]);
