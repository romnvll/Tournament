<?php

require 'vendor/autoload.php';
require 'class/PersonneTableDao.class.php';
require 'class/planificationDao.class.php';


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$keyValidation = false;
$afficherCodePin = true;
$idterrain = null;
$template = $twig->load('VuePersonneTable.twig');
$tournoiId = null;

if (isset ($_POST['codePin'])) {
$key = $_POST['key'] ;
$tournoiId = $_POST['tournoiId'];
$tablePersonne = new PersonneTableDao();
$infoUser = $tablePersonne->verifierCodePinEtRecupererRencontres($_POST['key'],$_POST['codePin']);

$afficherCodePin = false ;

$idterrain = substr($_POST['key'], -1);

$affichagePlanification = new planificationDao();
$affichagePlanification = $affichagePlanification->getPlanificationsTerrainAvecDetails($idterrain,$tournoiId);


}



//verifier si la clé exist
if (  isset ($_GET['key']) ) {
$tablePersonne = new PersonneTableDao();
$keyExist = $tablePersonne->chercherCleUrl($_GET['key']);
$key = $_GET['key'] ;
$tournoiId = $_GET['tournoi_id'];
$keyValidation = true;







//test de la clé si elle est correcte :
  if ($keyExist) {
    $infoTablePersonne = $tablePersonne->recupererInformationsParCle($_GET['key']);
    
  }




}







echo $template->render([
  'email' => $_COOKIE['email'],
  'keyExist' => $keyExist,
  'keyValidation' => $keyValidation,
  'key' => $key,
  'infoUser' => $infoUser,
  'AfficherCodePin' => $afficherCodePin,
  'AffichagePlanification' => $affichagePlanification,
  'tournoiId' => $tournoiId,
  
  
 
  
]);
