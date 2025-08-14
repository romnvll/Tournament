<?php
require ('security.php');
require ('class/clubDao.class.php');
require 'vendor/autoload.php';
require 'class/labelsDao.class.php';
require 'class/categorie.class.php';
require_once 'class/SponsorDAO.class.php';

$sponsorDao = new SponsorDAO();
$categories = new CategorieDao();

$categorie = $categories->obtenirToutesLesCategories($userData['id'], 'id_categorie DESC');

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true,

]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('mesPreferences.twig');

$club = new ClubDAO();
$label = new LabelDao();


if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "Mot de passe mis à jour.";
} else {
    $message = null;
}


echo $template->render([
  'email' => $userData['email'],
  
  'pageEnCours' =>  'Users',
  'categories' => $categorie,
  'message' => $message,
  'idClub' => $userData['id'],
  'sponsors' => $sponsorDao->getSponsorsParClub($userData['id']),
  'idTournoi' => $_GET['id_tournoi'],
 
   
//'ListeDesTournois' => $tournoiDao->afficherLesTournois(),
//'AfficherClub' => $listeClub->afficherClubs(),
//'AfficherLesEquipes' => $listeDesEquipes->getAllEquipeByIdTournoi($_GET['idTournoi']),
//'AfficherLesPoules' => $poules->getAllPoulesByTournoi($_GET['idTournoi']),

]);
