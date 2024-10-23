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









echo $template->render([
  
  
 
  'AfficherCodePin' => $afficherCodePin,
  'AffichagePlanification' => $affichagePlanification,
  'tournoiId' => $tournoiId,
  'prenom' => $_SESSION['infoUser'][0]['Prenom'],
  'terrain' => $_SESSION['infoUser'][0]['terrainNom'],
  'missingScore' => $_GET['missing_score'],
  
 
  
]);
