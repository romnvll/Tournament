<?php
require ('security.php');
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

if (isset($_GET['NbrEquipeParPoule'])) {
  $PouleAuto = $_GET['NbrEquipeParPoule'];
}
else 
{
  $PouleAuto=6;
}


$tournois = new tournoiDao();
$equipesByCategorie = new EquipeDAO();
$afficheCategorie = new EquipeDAO();
$poules = new PouleManager();


//to DO
// si la poule existe l'indiquer sur cette page
$nomTournoi=$tournois->getTournoiById($_GET['id_tournoi'])['nom'];



$template = $twig->load('GestionPoules.twig');
echo $template->render([
  'email' => $_COOKIE['email'],
  'pageEnCours' => 'GestionDesPoules',

    'poules' => $poules->getAllPoulesByTournoi($_GET['id_tournoi']),
    'poulesverif' => $poules->getAllPoulesByTournoi($_GET['id_tournoi']),
    'nomDuTournoi' => $nomTournoi,
    'PoulesAuto' => $poules->creerPoules($_GET['id_tournoi'],$PouleAuto),
    'ListeDesTournois' => $tournois->afficherLesTournois(),
    'ListeDesCategorie' => $afficheCategorie->getAllCategorieByIdTournoi($_GET['id_tournoi']),
    'idTournoi' => $_GET['id_tournoi'],
    'nombreEquipeParPoule' => $PouleAuto,



]);


?>