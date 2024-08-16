<?php

use Twig\Node\Expression\ConstantExpression;

require 'vendor/autoload.php';
require 'class/PersonneTableDao.class.php';
require 'class/planificationDao.class.php';
require 'class/rencontreDao.class.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('VuePersonneTable.twig');

$keyExist=null;
$key=null;
$keyValidation = false;
$afficherCodePin = true;
$idterrain = null;

$tournoiId = null;
$infoUser=null;
$affichagePlanification=null;

session_start();


if (isset ($_SESSION['infoUser'][0]['url_key'])) {
 $affichagePlanification = new planificationDao();
 $tablePersonne = new PersonneTableDao();

 $keyExist = $tablePersonne->chercherCleUrl($_SESSION['infoUser'][0]['url_key']);

 $idterrain = $_SESSION['idterrain'];
 $tournoiId = $_SESSION['tournoiId'];
$affichagePlanification = $affichagePlanification->getPlanificationsTerrainAvecDetails($idterrain,$tournoiId);
$infoTablePersonne = $tablePersonne->recupererInformationsParCle($_GET['key']);


}

else {
  echo "erreur";
  exit(1);
}



if (isset($_GET['scoreEquipe1']) ) {
  $rencontre = new RencontreDAO();
  
  // A faire : si le champ est vide, passer le score à null
  if ($_GET['scoreEquipe1'] == "") {
    $rencontre->modifierRencontre($_GET['idRencontre'],null,9999);

  }
  else {
  // Mettre à jour la rencontre avec les scores
  $rencontre->modifierRencontre($_GET['idRencontre'],$_GET['scoreEquipe1'],9999);
  }
} 

if (isset($_GET['scoreEquipe2']) ) {
  $rencontre = new RencontreDAO();
  if ($_GET['scoreEquipe2'] == "") {
    // si le score est vide, on passe l'argument 9999 pour ne pas toucher au score
    $rencontre->modifierRencontre($_GET['idRencontre'],9999,null);

  }
  else {
    $rencontre->modifierRencontre($_GET['idRencontre'],9999,$_GET['scoreEquipe2']);

  }
  

  
} 





echo $template->render([
  
  
 
  'AfficherCodePin' => $afficherCodePin,
  'AffichagePlanification' => $affichagePlanification,
  'tournoiId' => $tournoiId,
  'prenom' => $_SESSION['infoUser'][0]['Prenom'],
  'terrain' => $_SESSION['infoUser'][0]['terrainNom']
  
  
 
  
]);
