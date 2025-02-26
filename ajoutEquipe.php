<?php
require 'security.php';
require 'vendor/autoload.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


require_once 'class/clubDao.class.php';
require_once 'class/databaseInformations.php';
require_once 'class/tournoiDao.class.php';
require_once 'class/pouleManagerDao.class.php';
require_once 'class/equipeDao.class.php';
require_once 'class/categorie.class.php';
$tournoiDao = new tournoiDao();

$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);


//si l'id du tournoi est égal à 0 alors on redirige vers la page ajoutEquipe avec l'id du dernier tournoi

foreach ($tousLesTournois as $tournoi) {
      // $dernierId = $tournoi['id'];
      }

//if ($_GET['idTournoi'] == "0") {
 // header("Location: ajoutEquipe.php?idTournoi=" . $dernierId);
  //  exit();
//}
//sinon on continue





if (($tournoiDao->droitTournoiClub($_GET['idTournoi'], $userData['id']) == null) and ($_GET['idTournoi'] != "0")) {
    
  exit;
}


$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();

$listeDesCategorie = new CategorieDao();

$template = $twig->load('ajoutEquipe.twig');



$dernierId = null;

foreach ($tousLesTournois as $tournoi) {

    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
        $dernierId = $tournoi['id'];

        
}

}


echo $template->render([
  'email' => $userData['email'],
  'logo' => $userData['logo'],
  'pageEncours' => 'ajoutEquipe',
  'tournoiEnCours' => $_GET['idTournoi'],
  'idTournoi' => $dernierId,
  'dernierTournoi' => $dernierId,
'ListeDesTournois' => $tournoiDao->afficherLesTournois($userData['id']),
'AfficherClub' => $listeClub->afficherClubs(),
'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),
'AfficheLesCategories' => $listeDesCategorie->obtenirToutesLesCategories(),

]);







?>
