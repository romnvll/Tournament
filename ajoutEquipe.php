<?php
require 'security.php';
require 'vendor/autoload.php';



$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


require 'class/clubDao.class.php';
require 'class/databaseInformations.php';
require 'class/tournoiDao.class.php';
require 'class/pouleManagerDao.class.php';
require 'class/equipeDao.class.php';
require 'class/categorie.class.php';
$tournoiDao = new tournoiDao();
//$tournoiDao->getAllTournoi();

$poules = new PouleManager();

$listeClub = new ClubDAO();
$listeDesEquipes= new EquipeDAO();

$listeDesCategorie = new CategorieDao();

$template = $twig->load('ajoutEquipe.twig');

$tousLesTournois = $tournoiDao->afficherLesTournois($userData['id']);

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
