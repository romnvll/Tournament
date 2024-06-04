<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/evenementsDao.class.php';
require 'class/terrainDao.class.php';
require('class/rencontreDao.class.php');


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('PlacementDesRencontres.twig');

$tournois = new tournoiDao();
$evenements = new EvenementDAO();
$terrain = new TerrainDao();

if (!isset($_GET['id_tournoi'])) {
    $listedestournois = $tournois->afficherLesTournois();
    $nbrterrain = null;
    $table = null;
    $listdecreneau = null;
} else {
   $listedestournois = $tournois->afficherLesTournois();
   $listeDesEvenements = $evenements->obtenirTousLesEvenements($_GET['id_tournoi']);
   $nbrterrain = $terrain->AfficherTerrains($_GET['id_tournoi']);
   $listdecreneaux = $evenements->listerLesCreneaux($_GET['id_tournoi']);
   
}

$tableauEvenements = array();
$rencontreDao = new RencontreDAO();
foreach ($listeDesEvenements as $evenement) {
  //var_dump($rencontreDao->getRencontreDetails($evenement['fk_Idrencontre']));
  //$tableauEvenements[$evenement['fk_Idcreneau']][$evenement['fk_Idterrain']] = $evenement['fk_Idrencontre'];
$tableauEvenements[$evenement['fk_Idcreneau']][$evenement['fk_Idterrain']] = $rencontreDao->getRencontreDetails($evenement['fk_Idrencontre']);

}


echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesRencontres',
    'idTournoi' => $_GET['id_tournoi'],
    'afficherRencontreByIdTournoi' =>  $rencontreDao->getRencontreByTournoi($_GET['id_tournoi']),
    
    //'genererPlacement' => $table,
    'tableauEvenements' => $tableauEvenements,
    'ListeDesTournois' => $listedestournois,
    'nbrterrain' => $nbrterrain,
    'listCreneaux' => $listdecreneaux,


]);
