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




if (($tournoiDao->droitTournoiClub($_GET['idTournoi'], $userData['id']) == null) and ($_GET['idTournoi'] != "0")) {
    
  exit;
}


$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();

$listeDesCategorie = new CategorieDao();

$template = $twig->load('ajoutEquipe.twig');



$dernierId = $_GET['idTournoi'];

foreach ($tousLesTournois as $tournoi) {

    if (isset($tournoi['isArchived']) && $tournoi['isArchived'] == 0) {
       $dernierId = $tournoi['id'];

        
}

}

if (isset($_GET['query'])) {
    $query = $_GET['query'];
}
else {
    $query = '';
}


echo $template->render([
  'email' => $userData['email'],
  'logo' => $userData['logo'],
  'pageEncours' => 'ajoutEquipe',
  'tournoiEnCours' => $_GET['idTournoi'],
  'idTournoi' => $_GET['idTournoi'],
  'dernierTournoi' => $dernierId,
'ListeDesTournois' => $tournoiDao->afficherLesTournois($userData['id']),
'AfficherClub' => $listeClub->afficherClubs(),
'AfficherLesEquipes' => $listeDesEquipes->rechercherEquipesDansTournoi($_GET['idTournoi'], $_GET['query']),
'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),
'AfficheLesCategories' => $listeDesCategorie->obtenirToutesLesCategories(),
'query' => $query,


]);







?>
