<?php
require 'security.php';
require 'vendor/autoload.php';
require 'class/tournoiDao.class.php';
require 'class/evenementsDao.class.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,

]);

$twig->addExtension(new \Twig\Extension\DebugExtension());
$template = $twig->load('PlacementDesRencontres.twig');

$tournois = new tournoiDao();
$evenements = new EvenementDAO();

if (!isset($_GET['id_tournoi'])) {
    $listedestournois = $tournois->afficherLesTournois();
    $table = null;
} else {
   $listedestournois = $tournois->afficherLesTournois();
   $listeDesEvenements = $evenements->obtenirTousLesEvenements($_GET['id_tournoi']);

    require('class/rencontreDao.class.php');
}


echo $template->render([
    'email' => $_COOKIE['email'],
    'pageEnCours' => 'GestionDesRencontres',
    'idTournoi' => $_GET['id_tournoi'],
    //'afficherRencontreByIdTournoi' =>  $recontreDao->afficherRencontreByIdTournoi($_GET['idTournoi']),
    
    //'genererPlacement' => $table,
    'ListeEvenements' => $listeDesEvenements,
    'ListeDesTournois' => $listedestournois,



]);
