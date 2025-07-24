<?php

use Twig\Node\Expression\ConstantExpression;

require 'vendor/autoload.php';
require 'class/PersonneTableDao.class.php';
require 'class/planificationDao.class.php';
require 'class/rencontreDao.class.php';
require 'class/tournoiDao.class.php';


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
$key = null;

session_start();


$tournoiId = $_SESSION['tournoiId'];
$tournoiDao = new TournoiDAO();
if ( $tournoiDao->getTournoiById($tournoiId)['gestionPartenaires'] == 1) {
  
  //recuperation des partenaires du club qui a organiser ce tournoi
  require_once 'class/SponsorDAO.class.php';
  $sponsorDao = new SponsorDAO();
  $listeDesPartenaires = $sponsorDao->getSponsorsActifParClub($tournoiDao->getTournoiById($tournoiId)['club_id']);
 
}
else {
  $listeDesPartenaires = null;
}



if (isset ($_SESSION['infoUser'][0]['url_key'])) {

 $affichagePlanification = new planificationDao();
 $tablePersonne = new PersonneTableDao();

 $keyExist = $tablePersonne->chercherCleUrl($_SESSION['infoUser'][0]['url_key']);

 $idterrain = $_SESSION['idterrain'];
 $tournoiId = $_SESSION['tournoiId'];


//$affichagePlanification = $affichagePlanification->getPlanificationsTerrainAvecDetails($idterrain,$tournoiId);

//detection du premier 0 dans le status des rencontres

// Supposons que $affichagePlanification soit le tableau renvoyé par getPlanificationsTerrainAvecDetails
$affichagePlanification = $affichagePlanification->getPlanificationsTerrainAvecDetails($idterrain, $tournoiId);

// Variable pour suivre si on a trouvé la première rencontre avec isTerminated = 0 ou 2
$premierTrouve = false;

// Parcours du tableau de planification
foreach ($affichagePlanification as &$planification) {
    // Si isTerminated est null, on passe à l'itération suivante
    if (is_null($planification['isTerminated'])) {
        $planification['est_premier_zero'] = false; // Ajoute explicitement false si besoin
        continue;
    }
    
    // Vérifie si c'est la première rencontre avec isTerminated à 0 ou 2
    if (!$premierTrouve && ($planification['isTerminated'] == 0 || $planification['isTerminated'] == 2)) {
        $planification['est_premier_zero'] = true; // Ajoute le champ avec la valeur true
        $premierTrouve = true; // Marque qu'on a trouvé le premier
    } else {
        $planification['est_premier_zero'] = false; // Ajoute le champ avec la valeur false pour les autres
    }
}



//fin de detection



if (isset($_GET['key'])) {
$infoTablePersonne = $tablePersonne->recupererInformationsParCle($_GET['key']);
}

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
  'partenaires' => $listeDesPartenaires,
  
  
 
  
]);
